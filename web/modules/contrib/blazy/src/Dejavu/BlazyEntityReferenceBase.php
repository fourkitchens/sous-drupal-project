<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;

/**
 * Base class for all entity reference formatters with field details.
 *
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickEntityReferenceFormatterBase
 */
abstract class BlazyEntityReferenceBase extends BlazyEntityMediaBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
  }

  /**
   * Build extra elements.
   */
  public function buildElementExtra(array &$element, $entity, $langcode) {
    parent::buildElementExtra($element, $entity, $langcode);

    $settings = &$element['settings'];

    // Layouts can be builtin, or field, if so configured.
    if (!empty($settings['layout'])) {
      $layout = $settings['layout'];
      if (strpos($layout, 'field_') !== FALSE && isset($entity->{$layout})) {
        $layout = $this->blazyEntity()->getFieldString($entity, $layout, $langcode);
      }
      $settings['layout'] = $layout;
    }

    // Classes, if so configured.
    if (!empty($settings['class']) && isset($entity->{$settings['class']})) {
      $settings['class'] = $this->blazyEntity()->getFieldString($entity, $settings['class'], $langcode);
    }
  }

  /**
   * Builds slide captions with possible multi-value fields.
   */
  public function getCaption(array &$element, $entity, $langcode) {
    parent::getCaption($element, $entity, $langcode);

    $settings = $element['settings'];
    $view_mode = $settings['view_mode'];

    // Title can be plain text, or link field.
    if (!empty($settings['title'])) {
      if (isset($entity->{$settings['title']})) {
        $element['caption']['title'] = $this->blazyEntity()->getFieldTextOrLink($entity, $settings['title'], $settings);
      }
      elseif (isset($element['item']) && $item = $element['item']) {
        if (($settings['title'] == 'title') && ($caption = trim($item->get('title')->getString()))) {
          $markup = Xss::filter($caption, BlazyDefault::TAGS);
          $element['caption']['title'] = ['#markup' => $markup];
        }
      }
    }

    // Link, if so configured.
    if (!empty($settings['link']) && isset($entity->{$settings['link']})) {
      $links = $this->blazyEntity()->getFieldRenderable($entity, $settings['link'], $view_mode);

      // Only simplify markups for known formatters registered by link.module.
      if ($links && isset($links['#formatter']) && in_array($links['#formatter'], ['link'])) {
        $links = [];
        foreach ($entity->{$settings['link']} as $link) {
          $links[] = $link->view($view_mode);
        }
      }
      $element['caption']['link'] = $links;
    }

    // Overlay, if so configured.
    if (!empty($settings['overlay']) && isset($entity->{$settings['overlay']})) {
      $element['caption']['overlay'] = $this->getOverlay($settings, $entity, $langcode);
    }
  }

  /**
   * Builds overlay placed within the caption.
   */
  public function getOverlay(array $settings, $entity, $langcode) {
    return $entity->get($settings['overlay'])->view($settings['view_mode']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    if (isset($element['layout'])) {
      $layout_description = $element['layout']['#description'];
      $element['layout']['#description'] = $this->t('Create a dedicated List (text - max number 1) field related to the caption placement to have unique layout per slide with the following supported keys: top, right, bottom, left, center, center-top, etc. Be sure its formatter is Key.') . ' ' . $layout_description;
    }

    if (isset($element['overlay']['#description'])) {
      $element['overlay']['#description'] .= ' ' . $this->t('The formatter/renderer is managed by the child formatter.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $views_ui    = $this->getFieldSetting('handler') == 'default';
    $bundles     = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];
    $strings     = ['text', 'string', 'list_string'];
    $strings     = $admin->getFieldOptions($bundles, $strings, $target_type);
    $texts       = ['text', 'text_long', 'string', 'string_long', 'link'];
    $texts       = $admin->getFieldOptions($bundles, $texts, $target_type);
    $links       = ['text', 'string', 'link'];

    return [
      'classes' => $strings,
      'images'  => $admin->getFieldOptions($bundles, ['image'], $target_type),
      'layouts' => $strings,
      'links'   => $admin->getFieldOptions($bundles, $links, $target_type),
      'titles'  => $texts,
      'vanilla' => TRUE,
    ] + parent::getScopedFormElements();
  }

}

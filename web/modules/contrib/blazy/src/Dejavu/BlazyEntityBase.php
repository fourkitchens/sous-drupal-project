<?php

namespace Drupal\blazy\Dejavu;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Base class for entity reference formatters without field details.
 *
 * @see \Drupal\blazy\Dejavu\BlazyEntityMediaBase
 */
abstract class BlazyEntityBase extends EntityReferenceFormatterBase {

  /**
   * Returns media contents.
   */
  public function buildElements(array &$build, $entities, $langcode) {
    foreach ($entities as $delta => $entity) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', [
          '@entity_type' => $entity->getEntityTypeId(),
          '@entity_id' => $entity->id(),
        ]);
        return $build;
      }

      $build['settings']['delta'] = $delta;
      $build['settings']['langcode'] = $langcode;
      if ($entity->id()) {
        $this->buildElement($build, $entity, $langcode);

        // Add the entity to cache dependencies so to clear when it is updated.
        $this->formatter()->getRenderer()->addCacheableDependency($build['items'][$delta], $entity);
      }
      else {
        $this->referencedEntities = NULL;
        // This is an "auto_create" item.
        $build['items'][$delta] = ['#markup' => $entity->label()];
      }

      $depth = 0;
    }
  }

  /**
   * Returns item contents.
   */
  public function buildElement(array &$build, $entity, $langcode) {
    $view_mode = empty($build['settings']['view_mode']) ? 'full' : $build['settings']['view_mode'];
    $delta = $build['settings']['delta'];

    $build['items'][$delta] = $this->formatter()->getEntityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element    = [];
    $definition = $this->getScopedFormElements();

    $definition['_views'] = isset($form['field_api_classes']);

    $this->admin()->buildSettingsForm($element, $definition);
    return $element;
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    $settings = array_merge($this->getCommonFieldDefinition(), $this->getSettings());
    $settings['third_party'] = $this->getThirdPartySettings();
    return $settings;
  }

  /**
   * Defines the common scope for both front and admin.
   */
  public function getCommonFieldDefinition() {
    $field = $this->fieldDefinition;
    return [
      'current_view_mode' => $this->viewMode,
      'field_name'        => $field->getName(),
      'field_type'        => $field->getType(),
      'entity_type'       => $field->getTargetEntityTypeId(),
      'plugin_id'         => $this->getPluginId(),
      'target_type'       => $this->getFieldSetting('target_type'),
    ];
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    $views_ui = $this->getFieldSetting('handler') == 'default';
    $bundles = $views_ui ? [] : $this->getFieldSetting('handler_settings')['target_bundles'];

    // @todo move common/ reusable properties somewhere.
    return [
      'settings'       => $this->getSettings(),
      'target_bundles' => $bundles,
      'view_mode'      => $this->viewMode,
    ] + $this->getCommonFieldDefinition();
  }

}

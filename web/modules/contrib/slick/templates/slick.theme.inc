<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\blazy\Blazy;
use Drupal\slick\SlickDefault;
use Drupal\slick\Entity\Slick;

/**
 * Prepares variables for slick.html.twig templates.
 */
function template_preprocess_slick(&$variables) {
  $element   = $variables['element'];
  $defaults  = SlickDefault::htmlSettings();
  $settings  = isset($element['#settings']) ? array_merge($defaults, $element['#settings']) : $defaults;
  $customs   = isset($element['#options']) ? $element['#options'] : [];
  $optionset = isset($element['#optionset']) ? $element['#optionset'] : Slick::loadWithFallback($settings['optionset']);
  $js        = $customs ? array_merge($optionset->getSettings(), $customs) : $optionset->getSettings();

  // Prepare attributes.
  $attributes = &$variables['attributes'];
  $custom_classes = empty($attributes['class']) ? [] : $attributes['class'];
  $attributes['class'] = array_merge(['slick'], $custom_classes);
  $content_attributes = new Attribute();
  $display = $variables['display'] = $settings['display'];

  $id = empty($settings['id']) ? Blazy::getHtmlId('slick') : $settings['id'];
  $attributes['id'] = $display == 'thumbnail' ? $id . '-thumbnail' : $id;

  // @see SlickManager::buildGrid(), and this should make sense.
  $settings['count']   = isset($settings['count']) ? $settings['count'] : count($element['#items']);
  $settings['unslick'] = $settings['unslick'] || $settings['count'] == 1;

  foreach (SlickDefault::jsSettings() as $key => $default) {
    $settings[$key] = isset($js[$key]) ? strip_tags($js[$key]) : $default;
  }

  // Blazy can still lazyload an unslick.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  $settings['lazy'] = $settings['lazy'] ?: $js['lazyLoad'];
  if ($display != 'thumbnail' && ($settings['lazy'] == 'blazy' || !empty($settings['blazy']))) {
    $js['lazyLoad'] = 'blazy';
  }

  // Make slick language-direction-aware.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->getDirection() == 'rtl') {
    $attributes['dir'] = $language->getDirection();
    $js['rtl'] = $language->getDirection() ? TRUE : FALSE;
  }

  // Prevents broken slick when only one item given, or an enforced unslick.
  if (!$settings['unslick']) {
    $content_attributes->setAttribute('id', $attributes['id'] . '-slider');

    $variables['arrow_attributes'] = new Attribute();
    $variables['arrow_attributes']['role'] = 'navigation';
    if ($display == 'main' && !empty($js['downArrow']) && !empty($js['downArrowTarget'])) {
      $variables['arrow_down_attributes'] = new Attribute();
    }

    // focusOnSelect won't work with empty slide value, so add proper selector.
    // Respects core Grid markups which may wrap .slick__slide within anon DIV.
    if (empty($js['slide']) && !empty($js['focusOnSelect'])) {
      $js['slide'] = ($js['rows'] == 1 && $js['slidesPerRow'] == 1) ? '.slick__slide' : $js['slide'];
    }

    // Add the configuration as JSON object into the slick container.
    $js = isset($variables['js']) ? array_merge($js, $variables['js']) : $js;
    if ($json = $optionset->removeDefaultValues($js)) {
      $content_attributes->setAttribute('data-slick', Json::encode($json));
    }
  }

  // Pass settings and attributes to twig.
  $variables['js'] = $js;
  $variables['settings'] = $settings;
  $variables['content_attributes'] = $content_attributes;

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $item_settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $item_attributes = isset($item['attributes']) ? $item['attributes'] : [];
    $item_settings['current_item'] = $display;

    unset($item['settings'], $item['attributes'], $item['item']);

    $theme = $settings['vanilla'] ? 'vanilla' : ($display == 'thumbnail' ? 'thumbnail' : 'slide');
    $slide = [
      '#theme'      => 'slick_' . $theme,
      '#item'       => $item,
      '#delta'      => $delta,
      '#settings'   => $item_settings,
      '#attributes' => $item_attributes,
    ];
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for slick-wrapper.html.twig templates.
 */
function template_preprocess_slick_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }
}

/**
 * Prepares common variables for slick item templates.
 */
function _slick_preprocess_slick_item(&$variables) {
  foreach (['attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? NULL : [];
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : $default;
  }
}

/**
 * Prepares variables for slick-vanilla.html.twig templates.
 */
function template_preprocess_slick_vanilla(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-thumbnail.html.twig templates.
 */
function template_preprocess_slick_thumbnail(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-slide.html.twig templates.
 */
function template_preprocess_slick_slide(&$variables) {
  _slick_preprocess_slick_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
  }

  $item = &$variables['item'];
  $settings = &$variables['settings'];
  $variables['caption_attributes'] = new Attribute();

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unslick grid.
  // wrapper: Don't add divities for a single item to have clean markups.
  $item['slide']         = isset($item['slide']) ? $item['slide'] : [];
  $item['caption']       = array_filter($item['caption']);
  $settings['split']     = !empty($item) && (!empty($settings['caption']) || !empty($settings['title']));
  $settings['data']      = !empty($item['caption']['alt']) || !empty($item['caption']['title']) || !empty($item['caption']['data']);
  $settings['fullwidth'] = !empty($settings['skin']) && strpos($settings['skin'], 'full') !== FALSE;
  $settings['grid']      = empty($settings['grid']) ? FALSE : $settings['grid'];
  $settings['detroy']    = $settings['current_item'] == 'main' && !empty($settings['grid']) && !empty($settings['unslick']);
  $settings['wrapper']   = $settings['count'] > 1 && $settings['current_item'] != 'grid';
}

/**
 * Prepares variables for slick-grid.html.twig templates.
 */
function template_preprocess_slick_grid(&$variables) {
  $variables['settings'] = $variables['element']['#settings'];
  $variables['delta']    = isset($variables['element']['#delta']) ? $variables['element']['#delta'] : 0;
  $variables['grid_id']  = 'grid';

  $settings = $variables['settings'];

  // By default Slick only supports Grid Foundation, adds relevant grid_id for
  // optional Style: CSS3 Columns, and probably future flexbox.
  if (!empty($settings['style']) && $settings['style'] != 'slick') {
    $variables['grid_id'] = $settings['style'];
  }

  if (!empty($settings['media_switch'])) {
    $switch = str_replace('_', '-', $settings['media_switch']);
    $variables['attributes']['data-' . $switch . '-gallery'] = TRUE;
  }

  $variables['items'] = [];
  foreach ($variables['element']['#items'] as $delta => $item) {
    $settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $settings['current_item'] = 'grid';

    $item_attributes = empty($item['attributes']) ? [] : $item['attributes'];
    unset($item['settings'], $item['attributes']);

    $slide['content'] = [
      '#theme'    => empty($settings['vanilla']) ? 'slick_slide' : 'slick_vanilla',
      '#item'     => $item,
      '#delta'    => $delta,
      '#settings' => $settings,
    ];

    $slide['attributes'] = new Attribute($item_attributes);
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

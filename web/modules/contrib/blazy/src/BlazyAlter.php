<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FormatterInterface;
use Drupal\editor\Entity\Editor;

/**
 * Provides hook_alter() methods for Blazy.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class BlazyAlter {

  /**
   * Implements hook_config_schema_info_alter().
   */
  public static function configSchemaInfoAlter(array &$definitions, $formatter = 'blazy_base', array $settings = []) {
    if (isset($definitions[$formatter])) {
      $mappings = &$definitions[$formatter]['mapping'];
      $settings = $settings ?: BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
      foreach ($settings as $key => $value) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($value);
        $type = $type == 'double' ? 'float' : $type;
        $mappings[$key]['type'] = $key == 'breakpoints' ? 'mapping' : (is_array($value) ? 'sequence' : $type);

        if (!is_array($value)) {
          $mappings[$key]['label'] = Unicode::ucfirst(str_replace('_', ' ', $key));
        }
      }

      // @todo remove custom breakpoints anytime before 3.x as per #3105243.
      if (isset($mappings['breakpoints'])) {
        foreach (['xs', 'sm', 'md', 'lg', 'xl'] as $breakpoint) {
          $mappings['breakpoints']['mapping'][$breakpoint]['type'] = 'mapping';
          foreach (['breakpoint', 'width', 'image_style'] as $item) {
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['type']  = 'string';
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['label'] = Unicode::ucfirst(str_replace('_', ' ', $item));
          }
        }
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  public static function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'blazy') {
      if ($path = blazy_libraries_get_path('blazy')) {
        $libraries['blazy']['js'] = ['/' . $path . '/blazy.js' => ['weight' => -4]];
      }

      if (blazy()->configLoad('io.enabled')) {
        if (blazy()->configLoad('io.unblazy')) {
          $dependencies = ['core/drupal', 'blazy/bio.media', 'blazy/loading'];
          $libraries['load']['dependencies'] = $dependencies;
        }
        else {
          $libraries['load']['dependencies'][] = 'blazy/bio.media';
        }
      }
    }

    if ($extension === 'media' && isset($libraries['oembed.frame'])) {
      $libraries['oembed.frame']['dependencies'][] = 'blazy/oembed';
    }
  }

  /**
   * Implements hook_blazy_settings_alter().
   */
  public static function blazySettingsAlter(array &$build, $items) {
    $settings = &$build['settings'];

    // Sniffs for Views to allow block__no_wrapper, views_no_wrapper, etc.
    if (function_exists('views_get_current_view') && $view = views_get_current_view()) {
      $settings['view_name'] = $view->storage->id();
      $settings['current_view_mode'] = $view->current_display;
      $plugin_id = is_null($view->style_plugin) ? "" : $view->style_plugin->getPluginId();
      $settings['view_plugin_id'] = empty($settings['view_plugin_id']) ? $plugin_id : $settings['view_plugin_id'];
    }
  }

  /**
   * Checks if Entity/Media Embed is enabled.
   */
  public static function isCkeditorApplicable(Editor $editor) {
    foreach (['entity_embed', 'media_embed'] as $filter) {
      if (!$editor->isNew()
        && $editor->getFilterFormat()->filters()->has($filter)
        && $editor->getFilterFormat()
          ->filters($filter)
          ->getConfiguration()['status']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Implements hook_ckeditor_css_alter().
   */
  public static function ckeditorCssAlter(array &$css, Editor $editor) {
    if (self::isCkeditorApplicable($editor)) {
      $path = base_path() . drupal_get_path('module', 'blazy');
      $css[] = $path . '/css/components/blazy.media.css';
      $css[] = $path . '/css/components/blazy.preview.css';
      $css[] = $path . '/css/components/blazy.ratio.css';
    }
  }

  /**
   * Provides the third party formatters where full blown Blazy is not worthy.
   *
   * The module doesn't automatically convert the relevant theme to use Blazy,
   * however two attributes are provided: `data-b-lazy` and `data-b-preview`
   * which can be used to override a particular theme to use Blazy.
   *
   * The `data-b-lazy`is a flag indicating Blazy is enabled.
   * The `data-b-preview` is a flag indicating Blazy in CKEditor preview mode
   * via Entity/Media Embed which normally means Blazy should be disabled
   * due to CKEditor not supporting JS assets.
   *
   * @see \Drupal\blazy\Blazy::preprocessBlazy()
   * @see \Drupal\blazy\Blazy::preprocessField()
   * @see \Drupal\blazy\Blazy::preprocessFileVideo()
   * @see blazy_preprocess_file_video()
   */
  public static function thirdPartyFormatters() {
    $formatters = ['file_video'];
    blazy()->getModuleHandler()->alter('blazy_third_party_formatters', $formatters);
    return array_unique($formatters);
  }

  /**
   * Overrides variables for field.html.twig templates.
   */
  public static function thirdPartyPreprocessField(array &$variables) {
    $element = $variables['element'];
    $settings = empty($element['#blazy']) ? [] : $element['#blazy'];
    $settings['third_party'] = $element['#third_party_settings'];
    $is_preview = Blazy::isPreview();

    foreach ($variables['items'] as &$item) {
      if (empty($item['content'])) {
        continue;
      }

      $item_attributes = &$item['content'][isset($item['content']['#attributes']) ? '#attributes' : '#item_attributes'];
      $item_attributes['data-b-lazy'] = TRUE;
      if ($is_preview) {
        $item_attributes['data-b-preview'] = TRUE;
      }
    }

    // Attaches Blazy libraries here since Blazy is not the formatter.
    $attachments = blazy()->attach($settings);
    $variables['#attached'] = empty($variables['#attached']) ? $attachments : NestedArray::mergeDeep($variables['#attached'], $attachments);
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   */
  public static function fieldFormatterThirdPartySettingsForm(FormatterInterface $plugin) {
    if (in_array($plugin->getPluginId(), self::thirdPartyFormatters())) {
      return [
        'blazy' => [
          '#type' => 'checkbox',
          '#title' => 'Blazy',
          '#default_value' => $plugin->getThirdPartySetting('blazy', 'blazy', FALSE),
        ],
      ];
    }
    return [];
  }

  /**
   * Implements hook_field_formatter_settings_summary_alter().
   */
  public static function fieldFormatterSettingsSummaryAlter(&$summary, $context) {
    $on = $context['formatter']->getThirdPartySetting('blazy', 'blazy', FALSE);
    if ($on && in_array($context['formatter']->getPluginId(), self::thirdPartyFormatters())) {
      $summary[] = 'Blazy';
    }
  }

  /**
   * Attaches Colorbox if so configured.
   */
  public static function attachColorbox(array &$load, $attach = []) {
    if (\Drupal::hasService('colorbox.attachment')) {
      $dummy = [];
      \Drupal::service('colorbox.attachment')->attach($dummy);
      $load = isset($dummy['#attached']) ? NestedArray::mergeDeep($load, $dummy['#attached']) : $load;
      $load['library'][] = 'blazy/colorbox';
      unset($dummy);
    }
  }

}

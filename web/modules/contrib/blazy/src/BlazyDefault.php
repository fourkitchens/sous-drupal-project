<?php

namespace Drupal\blazy;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 */
class BlazyDefault {

  /**
   * Defines constant for the supported text tags.
   */
  const TAGS = ['a', 'em', 'strong', 'h2', 'p', 'span', 'ul', 'ol', 'li'];

  /**
   * The current class instance.
   *
   * @var self
   */
  private static $instance = NULL;

  /**
   * Returns the static instance of this class.
   */
  public static function getInstance() {

    if (is_null(self::$instance)) {
      self::$instance = new BlazyDefault();
    }

    return self::$instance;
  }

  /**
   * Returns Blazy specific breakpoints.
   *
   * @todo remove custom breakpoints anytime before blazy:3.x.
   */
  public static function getConstantBreakpoints() {
    return ['xs', 'sm', 'md', 'lg', 'xl'];
  }

  /**
   * Returns alterable plugin settings to pass the tests.
   */
  public function alterableSettings(array &$settings = []) {
    $context = ['class' => get_called_class()];
    \Drupal::moduleHandler()->alter('blazy_base_settings', $settings, $context);

    return $settings;
  }

  /**
   * Returns settings provided by various UI.
   */
  public static function anywhereSettings() {
    return ['fx' => '', 'style' => ''];
  }

  /**
   * Returns basic plugin settings.
   */
  public static function baseSettings() {
    $settings = [
      'cache'             => 0,
      'current_view_mode' => '',
      'skin'              => '',
    ] + self::anywhereSettings();

    blazy_alterable_settings($settings);
    return $settings;
  }

  /**
   * Returns cherry-picked settings for field formatters and Views fields.
   */
  public static function cherrySettings() {
    return [
      'box_style'       => '',
      'image_style'     => '',
      'media_switch'    => '',
      'ratio'           => '',
      'thumbnail_style' => '',
      '_uri'            => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function baseImageSettings() {
    return [
      'background'             => FALSE,
      'box_caption'            => '',
      'box_caption_custom'     => '',
      'box_media_style'        => '',
      'caption'                => [],
      'responsive_image_style' => '',
    ] + self::cherrySettings();
  }

  /**
   * Returns deprecated settings.
   *
   * @todo remove custom breakpoints anytime before 3.x.
   */
  public static function deprecatedSettings() {
    return [
      'breakpoints' => [],
      'sizes'       => '',
      'grid_header' => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function imageSettings() {
    return [
      'icon'      => '',
      'layout'    => '',
      'view_mode' => '',
    ] + self::baseSettings() + self::baseImageSettings() + self::deprecatedSettings();
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsSettings() {
    return [
      'class'   => '',
      'id'      => '',
      'image'   => '',
      'link'    => '',
      'overlay' => '',
      'title'   => '',
      'vanilla' => FALSE,
    ];
  }

  /**
   * Returns fieldable entity formatter and Views settings.
   */
  public static function extendedSettings() {
    return self::viewsSettings() + self::imageSettings();
  }

  /**
   * Returns optional grid field formatter and Views settings.
   */
  public static function gridSettings() {
    return [
      'grid'        => 0,
      'grid_header' => '',
      'grid_medium' => 0,
      'grid_small'  => 0,
    ] + self::anywhereSettings();
  }

  /**
   * Returns sensible default options common for Views lacking of UI.
   */
  public static function lazySettings() {
    return [
      'blazy' => TRUE,
      'lazy'  => 'blazy',
      'ratio' => 'fluid',
    ];
  }

  /**
   * Returns sensible default options common for entities lacking of UI.
   */
  public static function entitySettings() {
    return [
      'media_switch' => 'media',
      'rendered'     => FALSE,
      'view_mode'    => 'default',
      '_detached'    => TRUE,
    ] + self::lazySettings();
  }

  /**
   * Returns default options common for rich Media entities: Facebook, etc.
   *
   * This basically disables few Blazy features for rendered-entity-like.
   */
  public static function richSettings() {
    return [
      'background'   => FALSE,
      'lazy'         => '',
      'lightbox'     => FALSE,
      'media_switch' => '',
      'placeholder'  => '',
      'resimage'     => FALSE,
      'use_loading'  => FALSE,
      'type'         => 'rich',
    ] + self::anywhereSettings();
  }

  /**
   * Returns shared global form settings which should be consumed at formatters.
   */
  public static function uiSettings() {
    return [
      'one_pixel'           => TRUE,
      'native'              => FALSE,
      'noscript'            => FALSE,
      'placeholder'         => '',
      'responsive_image'    => FALSE,
      'unstyled_extensions' => '',
    ] + self::anywhereSettings();
  }

  /**
   * Returns sensible default container settings to shutup notices when lacking.
   */
  public static function htmlSettings() {
    return [
      'blazy_data' => [],
      'lightbox'   => FALSE,
      'namespace'  => 'blazy',
      'id'         => '',
      'is_preview' => FALSE,
      '_richbox'   => FALSE,
      'route_name' => '',
      'use_field'  => FALSE,
      'unstyled'   => FALSE,
      'view_name'  => '',
    ] + self::imageSettings() + self::uiSettings();
  }

  /**
   * Returns sensible default item settings to shutup notices when lacking.
   */
  public static function itemSettings() {
    return [
      '_api'           => FALSE,
      'bundle'         => '',
      'classes'        => [],
      'content_url'    => '',
      'delta'          => 0,
      'embed_url'      => '',
      'entity_type_id' => '',
      'extension'      => '',
      'image_url'      => '',
      'item_id'        => 'blazy',
      'lazy_attribute' => 'src',
      'lazy_class'     => 'b-lazy',
      'padding_bottom' => '',
      'placeholder_fx' => '',
      'placeholder_ui' => '',
      'player'         => FALSE,
      'resimage'       => FALSE,
      'scheme'         => '',
      'type'           => 'image',
      'uri'            => '',
      'use_data_uri'   => FALSE,
      'use_loading'    => TRUE,
      'use_media'      => FALSE,
      'height'         => NULL,
      'width'          => NULL,
    ] + self::htmlSettings();
  }

  /**
   * Returns blazy theme properties, its image and container attributes.
   *
   * The reserved attributes is defined before entering Blazy as bonus variable.
   * Consider other bonuses: title and content attributes at a later stage.
   */
  public static function themeProperties() {
    return [
      'attributes',
      'captions',
      'content',
      'iframe',
      'image',
      'icon',
      'item',
      'item_attributes',
      'noscript',
      'overlay',
      'preface',
      'postscript',
      'settings',
      'url',
    ];
  }

  /**
   * Returns additional/ optional blazy theme attributes.
   *
   * The attributes mentioned here are only instantiated at theme_blazy() and
   * might be an empty array, not instanceof \Drupal\Core\Template\Attribute.
   */
  public static function themeAttributes() {
    return ['caption', 'media', 'url', 'wrapper'];
  }

}

<?php

namespace Drupal\blazy;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Template\Attribute;

/**
 * Provides common blazy utility static methods.
 */
class Blazy implements BlazyInterface {

  // @todo remove at blazy:8.x-3.0 or sooner.
  use BlazyDeprecatedTrait;

  /**
   * The blazy HTML ID.
   *
   * @var int
   */
  private static $blazyId;

  /**
   * Prepares variables for blazy.html.twig templates.
   */
  public static function preprocessBlazy(array &$variables) {
    $element = $variables['element'];
    foreach (BlazyDefault::themeProperties() as $key) {
      $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
    }

    // Provides optional attributes, see BlazyFilter.
    foreach (BlazyDefault::themeAttributes() as $key) {
      $key = $key . '_attributes';
      $variables[$key] = empty($element["#$key"]) ? [] : new Attribute($element["#$key"]);
    }

    // Provides sensible default html settings to shutup notices when lacking.
    $settings  = &$variables['settings'];
    $settings += BlazyDefault::itemSettings();

    // Do not proceed if no URI is provided.
    if (empty($settings['uri'])) {
      return;
    }

    // URL and dimensions are built out at BlazyManager::preRenderBlazy().
    // Still provides a failsafe for direct call to theme_blazy().
    if (empty($settings['_api'])) {
      self::urlAndDimensions($settings, $variables['item']);
    }

    // Allows rich Media entities stored within `content` to take over.
    if (empty($variables['content'])) {
      self::buildMedia($variables);
    }

    // Aspect ratio to fix layout reflow with lazyloaded images responsively.
    // This is outside 'lazy' to allow non-lazyloaded iframe/content use it too.
    $settings['ratio'] = empty($settings['width']) ? '' : $settings['ratio'];
    if ($settings['ratio']) {
      self::aspectRatioAttributes($variables['attributes'], $settings);
    }

    // Makes a little order here due to twig ignoring the preset priority.
    $attributes = &$variables['attributes'];
    $classes = empty($attributes['class']) ? [] : $attributes['class'];
    $attributes['class'] = array_merge(['media', 'media--blazy'], $classes);
  }

  /**
   * {@inheritdoc}
   */
  public static function buildMedia(array &$variables) {
    $settings = $variables['settings'];

    // (Responsive) image is optional for Video, or image as CSS background.
    if (empty($settings['background'])) {
      if (!empty($settings['responsive_image_style_id'])) {
        self::buildResponsiveImage($variables);
      }
      else {
        self::buildImage($variables);
      }
    }

    // Prepare a media player, and allow a tiny video preview without iframe.
    if ($settings['use_media'] && empty($settings['_noiframe'])) {
      self::buildIframe($variables);
    }

    // (Responsive) image is optional for Video, or image as CSS background.
    if ($variables['image']) {
      self::imageAttributes($variables);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function urlAndDimensions(array &$settings, $item = NULL) {
    // BlazyFilter, or image style with crop, may already set these.
    BlazyUtil::imageDimensions($settings, $item);

    // Provides image url based on the given settings.
    BlazyUtil::imageUrl($settings);

    // The SVG placeholder should accept either original, or styled image.
    $is_media = in_array($settings['type'], ['audio', 'video']);
    $settings['placeholder'] = empty($settings['placeholder']) ? BlazyUtil::generatePlaceholder($settings['width'], $settings['height']) : $settings['placeholder'];
    $settings['use_media'] = $settings['embed_url'] && $is_media;
    $settings['use_loading'] = empty($settings['is_preview']) ? $settings['use_loading'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildResponsiveImage(array &$variables) {
    $settings = $variables['settings'];
    $attributes = empty($settings['is_preview']) ? [
      'data-b-lazy' => $settings['one_pixel'],
      'data-placeholder' => $settings['placeholder'],
    ] : [];
    $variables['image'] += [
      '#type' => 'responsive_image',
      '#responsive_image_style_id' => $settings['responsive_image_style_id'],
      '#uri' => $settings['uri'],
      '#attributes' => $attributes,
    ];
  }

  /**
   * Modifies variables for blazy (non-)lazyloaded image.
   */
  public static function buildImage(array &$variables) {
    $settings = $variables['settings'];

    // Supports either lazy loaded image, or not.
    $variables['image'] += [
      '#theme' => 'image',
      '#uri' => !empty($settings['is_preview']) || empty($settings['lazy']) ? $settings['image_url'] : $settings['placeholder'],
    ];
  }

  /**
   * Modifies $variables to provide optional (Responsive) image attributes.
   */
  public static function imageAttributes(array &$variables) {
    $item = $variables['item'];
    $settings = &$variables['settings'];
    $image = &$variables['image'];
    $attributes = &$variables['item_attributes'];

    // Respects hand-coded image attributes.
    if ($item) {
      if (!isset($attributes['alt'])) {
        $attributes['alt'] = empty($item->alt) ? NULL : trim($item->alt);
      }

      // Do not output an empty 'title' attribute.
      if (isset($item->title) && (mb_strlen($item->title) != 0)) {
        $attributes['title'] = trim($item->title);
      }
    }

    // Only output dimensions for non-svg. Respects hand-coded image attributes.
    // Do not pass it to $attributes to also respect both (Responsive) image.
    if (!isset($attributes['width']) && empty($settings['unstyled'])) {
      $image['#height'] = $settings['height'];
      $image['#width'] = $settings['width'];
    }

    $attributes['class'][] = 'media__image';
    self::commonAttributes($attributes, $variables['settings']);
    $image['#attributes'] = empty($image['#attributes']) ? $attributes : NestedArray::mergeDeep($image['#attributes'], $attributes);

    // Provides a noscript if so configured, before any lazy defined.
    if (!empty($settings['noscript']) && empty($settings['is_preview'])) {
      self::buildNoscriptImage($variables);
    }

    // Provides [data-(src|lazy)] for (Responsive) image, after noscript.
    if (!empty($settings['lazy'])) {
      self::lazyAttributes($image['#attributes'], $settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function iframeAttributes(array &$settings) {
    if (empty($settings['is_preview'])) {
      $attributes['data-src'] = $settings['embed_url'];
      $attributes['src'] = 'about:blank';
      $attributes['class'][] = 'b-lazy';
      $attributes['allowfullscreen'] = TRUE;
    }
    else {
      $attributes['src'] = $settings['embed_url'];
      $attributes['sandbox'] = TRUE;
    }

    $attributes['class'][] = 'media__iframe';
    self::commonAttributes($attributes, $settings);
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildIframe(array &$variables) {
    $settings = &$variables['settings'];
    $settings['player'] = empty($settings['lightbox']) && $settings['media_switch'] == 'media';

    if (empty($variables['url'])) {
      $variables['image'] = empty($settings['media_switch']) ? [] : $variables['image'];

      // Pass iframe attributes to template.
      $variables['iframe'] = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => self::iframeAttributes($settings),
      ];

      // Iframe is removed on lazyloaded, puts data at non-removable storage.
      $variables['attributes']['data-media'] = Json::encode(['type' => $settings['type']]);
    }
  }

  /**
   * Provides (Responsive) image noscript if so configured.
   */
  public static function buildNoscriptImage(array &$variables) {
    $settings = $variables['settings'];
    $noscript = $variables['image'];
    $noscript['#uri'] = empty($settings['responsive_image_style_id']) ? $settings['image_url'] : $settings['uri'];
    $noscript['#attributes']['data-b-noscript'] = TRUE;

    $variables['noscript'] = [
      '#type' => 'inline_template',
      '#template' => '{{ prefix | raw }}{{ noscript }}{{ suffix | raw }}',
      '#context' => [
        'noscript' => $noscript,
        'prefix' => '<noscript>',
        'suffix' => '</noscript>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function lazyAttributes(array &$attributes, array $settings = []) {
    // Slick has its own class and methods: ondemand, anticipative, progressive.
    // @todo remove this condition once sub-modules have been aware of preview.
    if (empty($settings['is_preview'])) {
      $attributes['class'][] = $settings['lazy_class'];
      $attributes['data-' . $settings['lazy_attribute']] = $settings['image_url'];
    }
  }

  /**
   * Provide common attributes for IMG, IFRAME, VIDEO, DIV, etc. elements.
   */
  public static function commonAttributes(array &$attributes, array $settings = []) {
    $attributes['class'][] = 'media__element';

    // Support browser native lazy loading as per 8/2019 specific to Chrome 76+.
    // See https://web.dev/native-lazy-loading/
    if (!empty($settings['native'])) {
      $attributes['loading'] = 'lazy';
    }
  }

  /**
   * Modifies container attributes with aspect ratio for iframe, image, etc.
   */
  public static function aspectRatioAttributes(array &$attributes, array &$settings) {
    $settings['ratio'] = empty($settings['ratio']) ? '' : str_replace(':', '', $settings['ratio']);

    if ($settings['height'] && $settings['ratio'] == 'fluid') {
      // If "lucky", Blazy/ Slick Views galleries may already set this once.
      // Lucky when you don't flatten out the Views output earlier.
      $padding = $settings['padding_bottom'] ?: round((($settings['height'] / $settings['width']) * 100), 2);
      self::inlineStyle($attributes, 'padding-bottom: ' . $padding . '%;');

      // Views rewrite results or Twig inline_template may strip out `style`
      // attributes, provide hint to JS.
      $attributes['data-ratio'] = $padding;
    }
  }

  /**
   * Provides container attributes for .blazy container: .field, .view, etc.
   */
  public static function containerAttributes(array &$attributes, array $settings = []) {
    $settings += ['namespace' => 'blazy'];
    $classes = empty($attributes['class']) ? [] : $attributes['class'];
    $attributes['data-blazy'] = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);

    // Provides data-LIGHTBOX-gallery to not conflict with original modules.
    if (!empty($settings['media_switch']) && $settings['media_switch'] != 'content') {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attributes['data-' . $switch . '-gallery'] = TRUE;
      $classes[] = 'blazy--' . $switch;
    }

    // Provides contextual classes relevant to the container: .field, or .view.
    // Sniffs for Views to allow block__no_wrapper, views__no_wrapper, etc.
    foreach (['field', 'view'] as $key) {
      if (!empty($settings[$key . '_name'])) {
        $name = str_replace('_', '-', $settings[$key . '_name']);
        $name = $key == 'view' ? 'view--' . $name : $name;
        $classes[] = $settings['namespace'] . '--' . $key;
        $classes[] = $settings['namespace'] . '--' . $name;
        if (!empty($settings['current_view_mode'])) {
          $view_mode = str_replace('_', '-', $settings['current_view_mode']);
          $classes[] = $settings['namespace'] . '--' . $name . '--' . $view_mode;
        }
      }
    }

    $attributes['class'] = array_merge(['blazy'], $classes);
  }

  /**
   * Overrides variables for responsive-image.html.twig templates.
   */
  public static function preprocessResponsiveImage(array &$variables) {
    $image = &$variables['img_element'];
    $attributes = &$variables['attributes'];
    $placeholder = empty($attributes['data-placeholder']) ? static::PLACEHOLDER : $attributes['data-placeholder'];

    // Bail out if a noscript is requested.
    // @todo figure out to not even enter this method, yet not break ratio, etc.
    if (!isset($attributes['data-b-noscript'])) {
      // Modifies <picture> [data-srcset] attributes on <source> elements.
      if (!$variables['output_image_tag']) {
        /** @var \Drupal\Core\Template\Attribute $source */
        if (isset($variables['sources']) && is_array($variables['sources'])) {
          foreach ($variables['sources'] as &$source) {
            $source->setAttribute('data-srcset', $source['srcset']->value());
            $source->setAttribute('srcset', '');
          }
        }

        // Prevents invalid IMG tag when one pixel placeholder is disabled.
        $image['#uri'] = $placeholder;
        $image['#srcset'] = '';

        // Cleans up the no-longer relevant attributes for controlling element.
        unset($attributes['data-srcset'], $image['#attributes']['data-srcset']);
      }
      else {
        // Modifies <img> element attributes.
        $image['#attributes']['data-srcset'] = $attributes['srcset']->value();
        $image['#attributes']['srcset'] = '';
      }

      // The [data-b-lazy] is a flag indicating 1px placeholder.
      // This prevents double-downloading the fallback image, if enabled.
      if (!empty($attributes['data-b-lazy'])) {
        $image['#uri'] = $placeholder;
      }

      // More shared-with-image attributes are set at self::imageAttributes().
      $image['#attributes']['class'][] = 'b-responsive';
    }

    // Cleans up the no-longer needed flags:
    foreach (['placeholder', 'b-lazy', 'b-noscript'] as $key) {
      unset($attributes['data-' . $key], $image['#attributes']['data-' . $key]);
    }
  }

  /**
   * Overrides variables for file-video.html.twig templates.
   */
  public static function preprocessFileVideo(array &$variables) {
    if ($files = $variables['files']) {
      if (empty($variables['attributes']['data-b-preview'])) {
        $variables['attributes']->addClass(['b-lazy']);
        foreach ($files as $file) {
          $source_attributes = &$file['source_attributes'];
          $source_attributes->setAttribute('data-src', $source_attributes['src']->value());
          $source_attributes->setAttribute('src', '');
        }
      }

      // Adds a poster image if so configured.
      if (isset($files[0], $files[0]['blazy']) && $blazy = $files[0]['blazy']) {
        if ($blazy->get('image') && $blazy->get('uri')) {
          $settings = $blazy->storage();
          $settings['_dimensions'] = TRUE;
          BlazyUtil::imageUrl($settings);
          if (!empty($settings['image_url'])) {
            $variables['attributes']->setAttribute('poster', $settings['image_url']);
          }
        }
      }

      $attrs = ['data-b-lazy', 'data-b-preview'];
      $variables['attributes']->addClass(['media__element']);
      $variables['attributes']->removeAttribute($attrs);
    }
  }

  /**
   * Overrides variables for field.html.twig templates.
   */
  public static function preprocessField(array &$variables) {
    $element = &$variables['element'];
    $settings = empty($element['#blazy']) ? [] : $element['#blazy'];

    // 1. Hence Blazy is not the formatter, lacks of settings.
    if (!empty($element['#third_party_settings']['blazy']['blazy'])) {
      BlazyAlter::thirdPartyPreprocessField($variables);
    }

    // 2. Hence Blazy is the formatter, has its settings.
    if (empty($settings['_grid'])) {
      self::containerAttributes($variables['attributes'], $settings);
    }
  }

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'blazy', $id = '') {
    if (!isset(static::$blazyId)) {
      static::$blazyId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    $id = empty($id) ? ($string . '-' . ++static::$blazyId) : $id;
    return Html::getId($id);
  }

  /**
   * Modifies inline style to not nullify others.
   */
  public static function inlineStyle(array &$attributes, $css) {
    $attributes['style'] = (isset($attributes['style']) ? $attributes['style'] : '') . $css;
  }

  /**
   * Returns URI from image item.
   */
  public static function uri($item) {
    $fallback = isset($item->uri) ? $item->uri : '';
    return empty($item) ? '' : (($entity = $item->entity) && empty($item->uri) ? $entity->getFileUri() : $fallback);
  }

  /**
   * Returns fake image item based on the given $attributes.
   */
  public static function image(array $attributes = []) {
    $item = new \stdClass();
    foreach (['uri', 'width', 'height', 'target_id', 'alt', 'title'] as $key) {
      if (isset($attributes[$key])) {
        $item->{$key} = $attributes[$key];
      }
    }
    return $item;
  }

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  public static function streamWrapperManager() {
    return \Drupal::hasService('stream_wrapper_manager') ? \Drupal::service('stream_wrapper_manager') : NULL;
  }

  /**
   * Returns a wrapper to pass tests, or DI where adding params is troublesome.
   */
  public static function routeMatch() {
    return \Drupal::routeMatch();
  }

  /**
   * Checks if Blazy is in CKEditor preview mode where no JS assets are loaded.
   */
  public static function isPreview() {
    return in_array(self::routeMatch()->getRouteName(), [
      'entity_embed.preview',
      'media.filter.preview',
    ]);
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  public static function configSchemaInfoAlter(array &$definitions, $formatter = 'blazy_base', array $settings = []) {
    BlazyAlter::configSchemaInfoAlter($definitions, $formatter, $settings);
  }

}

<?php

namespace Drupal\slick;

use Drupal\Component\Utility\NestedArray;
use Drupal\slick\Entity\Slick;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements BlazyManagerInterface, SlickManagerInterface.
 */
class SlickManager extends BlazyManagerBase implements SlickManagerInterface {

  /**
   * The slick skin manager service.
   *
   * @var \Drupal\slick\SlickSkinManagerInterface
   */
  protected $skinManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setSkinManager($container->get('slick.skin_manager'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSlick', 'preRenderSlickWrapper'];
  }

  /**
   * Returns slick skin manager service.
   */
  public function skinManager() {
    return $this->skinManager;
  }

  /**
   * Sets slick skin manager service.
   */
  public function setSkinManager(SlickSkinManagerInterface $skin_manager) {
    $this->skinManager = $skin_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $load = parent::attach($attach);

    $this->skinManager->attach($load, $attach);

    $this->moduleHandler->alter('slick_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function slick(array $build = []) {
    foreach (SlickDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'slick',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSlick']],
    ];
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  protected function prepareAttributes(array $build = []) {
    $settings = $build['settings'];
    $attributes = isset($build['attributes']) ? $build['attributes'] : [];

    if ($settings['display'] == 'main') {
      Blazy::containerAttributes($attributes, $settings);
    }
    return $attributes;
  }

  /**
   * Builds the Slick instance as a structured array ready for ::renderer().
   */
  public function preRenderSlick(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = &$build['settings'];
    $settings += SlickDefault::htmlSettings();

    // Adds helper class if thumbnail on dots hover provided.
    if (!empty($settings['thumbnail_effect']) && (!empty($settings['thumbnail_style']) || !empty($settings['thumbnail']))) {
      $dots_class[] = 'slick-dots--thumbnail-' . $settings['thumbnail_effect'];
    }

    // Adds dots skin modifier class if provided.
    if (!empty($settings['skin_dots'])) {
      $dots_class[] = 'slick-dots--' . str_replace('_', '-', $settings['skin_dots']);
    }

    if (isset($dots_class) && !empty($build['optionset'])) {
      $dots_class[] = $build['optionset']->getSetting('dotsClass') ?: 'slick-dots';
      $js['dotsClass'] = implode(" ", $dots_class);
    }

    // Overrides common options to re-use an optionset.
    if ($settings['display'] == 'main') {
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $js[$key] = empty($override) ? FALSE : TRUE;
        }
      }

      // Build the Slick grid if provided.
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = $this->buildGrid($build['items'], $settings);
      }
    }

    $build['attributes'] = $this->prepareAttributes($build);
    $build['options'] = isset($js) ? array_merge($build['options'], $js) : $build['options'];

    $this->moduleHandler->alter('slick_optionset', $build['optionset'], $settings);

    foreach (SlickDefault::themeProperties() as $key) {
      $element["#$key"] = $build[$key];
    }

    unset($build);
    return $element;
  }

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items = [], array &$settings = []) {
    $grids = [];

    // Enforces unslick with less items.
    if (empty($settings['unslick']) && !empty($settings['count'])) {
      $settings['unslick'] = $settings['count'] < $settings['visible_items'];
    }

    // Display all items if unslick is enforced for plain grid to lightbox.
    // Or when the total is less than visible_items.
    if (!empty($settings['unslick'])) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = 2;

      $grids[0] = $this->buildGridItem($items, 0, $settings);
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = !empty($settings['preserve_keys']);
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = count($grid_items);

      foreach ($grid_items as $delta => $grid_item) {
        $grids[] = $this->buildGridItem($grid_item, $delta, $settings);
      }
    }
    return $grids;
  }

  /**
   * Returns items as a grid item display.
   */
  public function buildGridItem(array $items, $delta, array $settings = []) {
    $slide = [
      '#theme'    => 'slick_grid',
      '#items'    => $items,
      '#delta'    => $delta,
      '#settings' => $settings,
    ];
    return ['slide' => $slide, 'settings' => $settings];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (SlickDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $slick = [
      '#theme'      => 'slick_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSlickWrapper']],
      // Satisfy CTools blocks as per 2017/04/06: 2804165.
      'items'       => [],
    ];

    $this->moduleHandler->alter('slick_build', $slick, $build['settings']);
    return empty($build['items']) ? [] : $slick;
  }

  /**
   * Prepare settings for the known module features, not necessarily users'.
   */
  protected function prepareSettings(array &$element, array &$build) {
    $settings = array_merge(SlickDefault::htmlSettings(), $build['settings']);
    $id       = $settings['id'] = Blazy::getHtmlId('slick', $settings['id']);
    $thumb_id = $id . '-thumbnail';
    $options  = $build['options'];

    // Disable draggable for Layout Builder UI to not conflict with UI sortable.
    if (strpos($settings['route_name'], 'layout_builder.') === 0 || !empty($settings['is_preview'])) {
      $options['draggable'] = FALSE;
    }

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Slick without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    if (!empty($settings['skin']) && $skins = $this->skinManager->getSkinsByGroup('main')) {
      if (isset($skins[$settings['skin']]['options'])) {
        $options = array_merge($options, $skins[$settings['skin']]['options']);
      }
    }

    // Additional settings.
    $build['optionset']   = $build['optionset'] ?: Slick::loadWithFallback($settings['optionset']);
    $settings['count']    = empty($settings['count']) ? count($build['items']) : $settings['count'];
    $settings['nav']      = $settings['nav'] ?: (empty($settings['vanilla']) && !empty($settings['optionset_thumbnail']) && isset($build['items'][1]));
    $settings['navpos']   = $settings['nav'] && !empty($settings['thumbnail_position']);
    $settings['vertical'] = $build['optionset']->getSetting('vertical');
    $mousewheel           = $build['optionset']->getSetting('mouseWheel');

    if ($settings['nav']) {
      $options['asNavFor']     = "#{$thumb_id}-slider";
      $optionset_thumbnail     = $build['optionset_tn'] = Slick::loadWithFallback($settings['optionset_thumbnail']);
      $mousewheel              = $optionset_thumbnail->getSetting('mouseWheel');
      $settings['vertical_tn'] = $optionset_thumbnail->getSetting('vertical');
    }
    else {
      // Pass extra attributes such as those from Commerce product variations to
      // theme_slick() since we have no asNavFor wrapper here.
      if (isset($element['#attributes'])) {
        $build['attributes'] = empty($build['attributes']) ? $element['#attributes'] : NestedArray::mergeDeep($build['attributes'], $element['#attributes']);
      }
    }

    // Supports Blazy multi-breakpoint or lightbox images if provided.
    // Cases: Blazy within Views gallery, or references without direct image.
    if (!empty($settings['check_blazy']) && !empty($settings['first_image'])) {
      $this->isBlazy($settings, $settings['first_image']);
    }

    // Formatters might have checked this, but not views, nor custom works.
    // Why the formatters should check it first? It is so known to children.
    if (empty($settings['_lazy'])) {
      $build['optionset']->whichLazy($settings);
    }

    $settings['mousewheel'] = $mousewheel;
    $settings['down_arrow'] = $build['optionset']->getSetting('downArrow');
    $build['options']       = $options;
    $build['settings']      = $settings;
    $attachments            = $this->attach($settings);
    $element['#settings']   = $settings;
    $element['#attached']   = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
  }

  /**
   * Returns slick navigation with the structured array similar to main display.
   */
  protected function buildNavigation(array &$build, array $thumbs) {
    $settings = $build['settings'];
    foreach (['items', 'options', 'settings'] as $key) {
      $build[$key] = isset($thumbs[$key]) ? $thumbs[$key] : [];
    }

    $settings                     = array_merge($settings, $build['settings']);
    $settings['optionset']        = $settings['optionset_thumbnail'];
    $settings['skin']             = $settings['skin_thumbnail'];
    $settings['display']          = 'thumbnail';
    $build['optionset']           = $build['optionset_tn'];
    $build['settings']            = $settings;
    $build['options']['asNavFor'] = "#" . $settings['id'] . '-slider';

    // The slick thumbnail navigation has the same structure as the main one.
    unset($build['optionset_tn']);
    return $this->slick($build);
  }

  /**
   * One slick_theme() to serve multiple displays: main, overlay, thumbnail.
   */
  public function preRenderSlickWrapper($element) {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare settings and assets.
    $this->prepareSettings($element, $build);

    // Checks if we have thumbnail navigation.
    $thumbs   = isset($build['thumb']) ? $build['thumb'] : [];
    $settings = $build['settings'];

    // Prevents unused thumb going through the main display.
    unset($build['thumb']);

    // Build the main Slick.
    $slick[0] = $this->slick($build);

    // Build the thumbnail Slick.
    if ($settings['nav'] && $thumbs) {
      $slick[1] = $this->buildNavigation($build, $thumbs);
    }

    // Reverse slicks if thumbnail position is provided to get CSS float work.
    if ($settings['navpos']) {
      $slick = array_reverse($slick);
    }

    // Collect the slick instances.
    $element['#items'] = $slick;
    $element['#cache'] = $this->getCacheMetadata($build);

    unset($build);
    return $element;
  }

  /**
   * Provides a shortcut to attach skins only if required.
   */
  public function attachSkin(array &$load, $attach = []) {
    $this->skinManager->attachSkin($load, $attach);
  }

  /**
   * Returns slick skins registered via SlickSkin plugin, or defaults.
   *
   * @todo TBD; deprecate this at slick:8.x-3.0 for slick:9.x-1.0.
   */
  public function getSkins() {
    return $this->skinManager->getSkins();
  }

  /**
   * Returns available slick skins by group.
   *
   * @todo TBD; deprecate this at slick:8.x-3.0 for slick:9.x-1.0.
   */
  public function getSkinsByGroup($group = '', $option = FALSE) {
    return $this->skinManager->getSkinsByGroup($group, $option);
  }

}

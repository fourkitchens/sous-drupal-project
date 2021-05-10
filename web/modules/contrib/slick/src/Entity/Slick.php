<?php

namespace Drupal\slick\Entity;

use Drupal\blazy\Blazy;

/**
 * Defines the Slick configuration entity.
 *
 * @ConfigEntityType(
 *   id = "slick",
 *   label = @Translation("Slick optionset"),
 *   list_path = "admin/config/media/slick",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "group",
 *     "skin",
 *     "breakpoints",
 *     "optimized",
 *     "options",
 *   }
 * )
 */
class Slick extends SlickBase implements SlickInterface {

  /**
   * The optionset group for easy selections.
   *
   * @var string
   */
  protected $group = '';

  /**
   * The skin name for the optionset.
   *
   * @var string
   */
  protected $skin = '';

  /**
   * The number of breakpoints for the optionset.
   *
   * @var int
   */
  protected $breakpoints = 0;

  /**
   * The flag indicating to optimize the stored options by removing defaults.
   *
   * @var bool
   */
  protected $optimized = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getSkin() {
    return $this->skin;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints() {
    return $this->breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function optimized() {
    return $this->optimized;
  }

  /**
   * Returns the Slick responsive settings.
   *
   * @return array
   *   The responsive options.
   */
  public function getResponsiveOptions() {
    if (empty($this->breakpoints)) {
      return FALSE;
    }
    $options = [];
    if (isset($this->options['responsives']['responsive'])) {
      $responsives = $this->options['responsives'];
      if ($responsives['responsive']) {
        foreach ($responsives['responsive'] as $delta => $responsive) {
          if (empty($responsives['responsive'][$delta]['breakpoint'])) {
            unset($responsives['responsive'][$delta]);
          }
          if (isset($responsives['responsive'][$delta])) {
            $options[$delta] = $responsive;
          }
        }
      }
    }
    return $options;
  }

  /**
   * Sets the Slick responsive settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings') {
    $this->options['responsives']['responsive'][$delta][$key] = $values;
    return $this;
  }

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @return array
   *   The cleaned out settings.
   */
  public function removeDefaultValues(array $js) {
    $config   = [];
    $defaults = self::defaultSettings();

    // Remove wasted dependent options if disabled, empty or not.
    if (!$this->optimized) {
      $this->removeWastedDependentOptions($js);
    }

    $config = array_diff_assoc($js, $defaults);

    // Remove empty lazyLoad, or left to default ondemand, to avoid JS error.
    if (empty($config['lazyLoad'])) {
      unset($config['lazyLoad']);
    }

    // Do not pass arrows HTML to JSON object as some are enforced.
    $excludes = [
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'prevArrow',
      'nextArrow',
    ];
    foreach ($excludes as $key) {
      unset($config[$key]);
    }

    // Clean up responsive options if similar to defaults.
    if ($responsives = $this->getResponsiveOptions()) {
      $cleaned = [];
      foreach ($responsives as $key => $responsive) {
        $cleaned[$key]['breakpoint'] = $responsives[$key]['breakpoint'];

        // Destroy responsive slick if so configured.
        if (!empty($responsives[$key]['unslick'])) {
          $cleaned[$key]['settings'] = 'unslick';
          unset($responsives[$key]['unslick']);
        }
        else {
          // Remove wasted dependent options if disabled, empty or not.
          if (!$this->optimized) {
            $this->removeWastedDependentOptions($responsives[$key]['settings']);
          }
          $cleaned[$key]['settings'] = array_diff_assoc($responsives[$key]['settings'], $defaults);
        }
      }
      $config['responsive'] = $cleaned;
    }
    return $config;
  }

  /**
   * Removes wasted dependent options, even if not empty.
   */
  public function removeWastedDependentOptions(array &$js) {
    foreach (self::getDependentOptions() as $key => $option) {
      if (isset($js[$key]) && empty($js[$key])) {
        foreach ($option as $dependent) {
          unset($js[$dependent]);
        }
      }
    }

    if (!empty($js['useCSS']) && !empty($js['cssEaseBezier'])) {
      $js['cssEase'] = $js['cssEaseBezier'];
    }
    unset($js['cssEaseOverride'], $js['cssEaseBezier']);
  }

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions() {
    $down_arrow = ['downArrowTarget', 'downArrowOffset'];
    return [
      'arrows'     => ['prevArrow', 'nextArrow', 'downArrow'] + $down_arrow,
      'downArrow'  => $down_arrow,
      'autoplay'   => [
        'pauseOnHover',
        'pauseOnDotsHover',
        'pauseOnFocus',
        'autoplaySpeed',
      ],
      'centerMode' => ['centerPadding'],
      'dots'       => ['dotsClass', 'appendDots'],
      'swipe'      => ['swipeToSlide'],
      'useCSS'     => ['cssEase', 'cssEaseBezier', 'cssEaseOverride'],
      'vertical'   => ['verticalSwiping'],
    ];
  }

  /**
   * Checks which lazyload to use.
   */
  public function whichLazy(array &$settings) {
    $lazy              = $this->getSetting('lazyLoad');
    $settings['blazy'] = $lazy == 'blazy' || !empty($settings['blazy']);
    $settings['lazy']  = $settings['blazy'] ? 'blazy' : $lazy;

    // Allows Blazy to take over for advanced features like Responsive image,
    // CSS background, video, etc.
    if (empty($settings['blazy'])) {
      $settings['lazy_class'] = $settings['lazy_attribute'] = 'lazy';
    }

    // Disable anything lazy-related settings if in preview mode.
    $settings['lazy'] = empty($settings['is_preview']) ? $settings['lazy'] : '';
    $settings['_lazy'] = TRUE;
  }

  /**
   * Returns the trusted HTML ID of a single slick instance.
   *
   * @deprecated in slick:8.x-2.0 and is removed from slick:8.x-2.2. Use
   *   \Drupal\blazy\Blazy::getHtmlId() instead.
   * @see https://www.drupal.org/node/3105648
   *
   * @return string
   *   The html ID.
   */
  public static function getHtmlId($string = 'slick', $id = '') {
    return Blazy::getHtmlId($string, $id);
  }

}

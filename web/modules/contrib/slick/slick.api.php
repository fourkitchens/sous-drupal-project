<?php

/**
 * @file
 * Hooks and API provided by the Slick module.
 */

/**
 * @defgroup slick_api Slick API
 * @{
 * Information about the Slick usages.
 *
 * Modules may implement any of the available hooks to interact with Slick.
 *
 * Slick may be configured using the web interface via sub-modules.
 * However below is a few sample coded ones. The simple API is to achieve
 * consistent markups working for various skins, and layouts for both coded
 * and sub-modules implementations.
 *
 * The expected parameters are:
 *   - items: A required array of slick contents: text, image or media.
 *   - options: An optional array of key:value pairs of custom JS options.
 *   - optionset: An optional optionset object to avoid multiple invocations.
 *   - settings: An array of key:value pairs of HTML/layout related settings
 *     which may contain optionset ID if no optionset above is provided.
 *
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 *
 * @section sec_quick Quick sample #1
 *
 * Returns the renderable array of a slick instance.
 * @code
 * function my_module_render_slick() {
 *   // Invoke the plugin class, or use a DI service container accordingly.
 *   $slick = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image-related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // Caption contains: alt, data, link, overlay, title.
 *   // Each item has keys: slide, caption, settings.
 *   $items[] = [
 *     // Use $formatter->getBlazy($element) to have lazyLoad where $element
 *     // contains:
 *     // item: Drupal\image\Plugin\Field\FieldType\ImageItem.
 *     'slide'   => ['#markup' => '<img src="https://drupal.org/files/One.gif" />'],
 *     'caption' => ['title' => t('Description #1')],
 *   ];
 *
 *   $items[] = [
 *     'slide'   => ['#markup' => '<img src="https://drupal.org/files/Two.gif" />'],
 *     'caption' => ['title' => t('Description #2')],
 *   ];
 *
 *   $items[] = [
 *     'slide'   => ['#markup' => '<img src="https://drupal.org/files/Three.gif" />'],
 *     'caption' => ['title' => t('Description #3')],
 *   ];
 *
 *   // Pass the $items to the array.
 *   $build['items'] = $items;
 *
 *   // If no optionset name is provided via $build['settings'], slick will
 *   // fallback to 'default'.
 *   // Optionally override 'default' optionset with custom JS options.
 *   $build['options'] = [
 *     'autoplay' => TRUE,
 *     'dots'     => TRUE,
 *     'arrows'   => FALSE,
 *   ];
 *
 *   // Build the slick.
 *   $element = $slick->build($build);
 *
 *   // Prepare $variables to pass into a .twig.html file.
 *   $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 * @see template_preprocess_slick()
 *
 * @section sec_detail Detailed sample #2
 *
 * This can go to some hook_preprocess() of a target html.twig, or any relevant
 * PHP file.
 *
 * The goal is to create a vertical newsticker, or tweets, with pure text only.
 * First, create an unformatted Views block, says 'Ticker' containing ~ 10
 * titles, or any data for the contents -- using EFQ, or static array will do.
 *
 * Returns the renderable array of a slick instance.
 * @code
 * function my_module_render_slick_detail() {
 *   // Invoke the plugin class, or use a DI service container accordingly.
 *   $slick = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // 1.
 *   // Optional $settings, can be removed.
 *   // Provides HTML settings with optionset name and ID, none of JS related.
 *   // To add JS key:value pairs, use #options below instead.
 *   // @see \Drupal\slick\SlickDefault for most supported settings.
 *   $build['settings'] = [
 *     // Optional optionset name, otherwise fallback to default.
 *     // 'optionset' => 'blog',
 *     // Optional skin name fetched from hook_slick_skins_info(), else none.
 *     // 'skin' => 'fullwidth',
 *     // Define the main ID. The rest are managed by the module.
 *     // If you provide ID, be sure unique per instance as it is cached.
 *     // Leave empty to be provided by the module.
 *     'id' => 'slick-ticker',
 *
 *     // Define cache max-age, default to -1 (Cache::PERMANENT) to permanently
 *     // cache the results. Hence a 1 hour is passed. Be sure it is an integer!
 *     'cache' => 3600,
 *   ];
 *
 *   // 3.
 *   // Obligatory #items, as otherwise empty slick.
 *   // Prepare #items, note the 'slide' key is to hold the actual slide
 *   // which can be pure and simple text, or any image/media file.
 *   // Meaning $rows can be text only, or image/audio/video, or a combination
 *   // of both.
 *   // To add caption/overlay, use 'caption' key with the supported sub-keys:
 *   // alt, data, link, overlay, title for complex content.
 *   // Sanitize each sub-key content accordingly.
 *   // @see template_preprocess_slick_slide() for more info.
 *   $items = [];
 *   foreach ($rows as $key => $row) {
 *     // Each item has keys: slide, caption, settings.
 *     $items[] = [
 *       'slide' => $row,
 *
 *       // Optional caption contains: alt, data, link, overlay, title.
 *       // If the above slide is an image, to add text caption, use:
 *       'caption' => ['title' => 'some-caption data'],
 *
 *       // Optional slide settings to manipulate layout, can be removed.
 *       // Individual slide supports some useful settings like layout, classes,
 *       // etc.
 *       // Meaning each slide can have different layout, or classes.
 *       'settings' => [
 *
 *         // Optionally add a custom layout, can be a static uniform value, or
 *         // dynamic one based on the relevant field value.
 *         'layout' => 'bottom',
 *
 *         // Optionally add a custom class, can be a static uniform class, or
 *         // dynamic one based on the relevant field value.
 *         'class' => 'slide--custom-class--' . $key,
 *       ],
 *     ];
 *   }
 *
 *   // Pass the $items to the array.
 *   $build['items'] = $items;
 *
 *   // 4.
 *   // Optional specific JS options, to re-use one optionset, can be removed.
 *   // Play with speed and options to achieve desired result.
 *   // @see config/install/slick.optionset.default.yml
 *   $build['options'] = [
 *     'arrows'    => FALSE,
 *     'autoplay'  => TRUE,
 *     'vertical'  => TRUE,
 *     'draggable' => FALSE,
 *   ];
 *
 *   // 5.
 *   // Build the slick with the arguments as described above.
 *   $element = $slick->build($build);
 *
 *   // Prepare $variables to pass into a .twig.html file.
 *  $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 *
 * @section sec_asnavfor AsNavFor sample #3
 *
 * The only requirement for asNavFor is optionset and optionset_thumbnail IDs:
 * @code
 * $build['settings']['optionset'] = 'optionset_name';
 * $build['settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 * @endcode
 *
 * The rest are optional, and will fallback to default:
 *   - $build['settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 *     Defined at the main settings.
 *
 *   - $build['settings']['id'] = 'slick-asnavfor';
 *     Only main display ID is needed. The thumbnail ID will be
 *     automatically created: 'slick-asnavfor-thumbnail', including the content
 *     attributes accordingly. If none provided, will fallback to incremented
 *     ID.
 *
 * See the HTML structure below to get a clear idea.
 *
 * 1. Main slider:
 * @code
 *   <div id="slick-asnavfor" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode
 * 2. Thumbnail slider:
 * @code
 *   <div id="slick-asnavfor-thumbnail" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode
 * The asnavfor targets are the 'slick-initialized' attributes, and managed by
 * the module automatically when using SlickManager::build().
 *
 * Returns the renderable array of slick instances.
 * @code
 * function my_module_render_slick_asnavfor() {
 *   // Invoke the plugin class, or use a DI service container accordingly.
 *   $slick = \Drupal::service('slick.manager');
 *
 *   // Access the formatter service for image related methods:
 *   $formatter = \Drupal::service('slick.formatter');
 *
 *   $build = [];
 *
 *   // 1. Main slider ---------------------------------------------------------
 *   // Add the main display items.
 *   $build['items'] = [];
 *
 *   $images = [1, 2, 3, 4, 6, 7];
 *   foreach ($images as $key) {
 *     // Each item has keys: slide, caption, settings.
 *     $build['items'][] = [
 *
 *       // Use $formatter->getBlazy($element) to have lazyLoad where $element
 *       // contains:
 *       // item: Drupal\image\Plugin\Field\FieldType\ImageItem.
 *       'slide'   => '<img src="/path/to/image-0' . $key . '.jpg">',
 *
 *       // Main caption contains: alt, data, link, overlay, title keys which
 *       // serve the purpose to have consistent markups and skins without
 *       // bothering much nor remembering what HTML tags and where to place to
 *       // provide for each purpose cosnsitently. CSS will do layout regardless
 *       // HTML composition.
 *       // If having more complex caption data, use 'data' key instead.
 *       // If the common layout doesn't satisfy the need, just override twig.
 *       'caption' => ['title' => 'Description #' . $key],
 *     ];
 *   }
 *
 *   // Optionally override the optionset.
 *   $build['options'] = [
 *     'arrows'        => FALSE,
 *     'centerMode'    => TRUE,
 *     'centerPadding' => '',
 *   ];
 *
 *   // Satisfy the asnavfor main settings.
 *   // @see \Drupal\slick\SlickDefault for most supported settings.
 *   $build['settings'] = [
 *     // The only required is 'optionset_thumbnail'.
 *     // Define both main and thumbnail optionset names at the main display.
 *     'optionset' => 'optionset_main_name',
 *     'optionset_thumbnail' => 'optionset_thumbnail_name',
 *
 *     // The rest is optional, just FYI.
 *     'id' => 'slick-asnavfor',
 *     'skin' => 'skin-main-name',
 *     'skin_thumbnail' => 'skin-thumbnail-name',
 *   ];
 *
 *   // 2. Thumbnail slider ----------------------------------------------------
 *   // The thumbnail array is grouped by 'thumb', yet has the same structured
 *   // array as the main display: items, options, optionset, settings.
 *   $build['thumb'] = ['items' => []];
 *   foreach ($images as $key) {
 *     // Each item has keys: slide, caption, settings.
 *     $build['thumb']['items'][] = [
 *       // Use $formatter->getThumbnail($settings) where $settings contain:
 *       // uri, image_style, height, width, alt, title.
 *       'slide'   => '<img src="/path/to/image-0' . $key . '.jpg">',
 *
 *       // Thumbnail caption accepts direct markup or custom renderable array
 *       // without any special key to be simple as much as complex.
 *       // Think Youtube playlist with scrolling nav: thumbnail, text, etc.
 *       'caption' => ['#markup' => 'Description #' . $key],
 *     ];
 *   }
 *
 *   // Optionally override 'optionset_thumbnail_name' with custom JS options.
 *   $build['thumb']['options'] = [
 *     'arrows'        => TRUE,
 *     'centerMode'    => TRUE,
 *     'centerPadding' => '10px',
 *
 *     // Be sure to have multiple slides for the thumbnail, otherwise nonsense.
 *     'slidesToShow'  => 5,
 *   ];
 *
 *   // Build the slick once.
 *   $element = $slick->build($build);
 *
 *   // Prepare variables to pass into a .twig.html file.
 *   $variables['slick'] = $element;
 *
 *   // Render the slick at a .twig.html file.
 *   // {{ slick }}
 *   // Or simply return the $element if a renderable array is expected.
 *   return $element;
 * }
 * @endcode
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 *
 * @section sec_skin Registering Slick skins
 *
 * To register a skin, copy \Drupal\slick\Plugin\slick\SlickSkin into your
 * module /src/Plugin/slick directory. Adjust everything accordinngly: rename
 * the file, change SlickSkin ID and label, change class name and its namespace,
 * define skin name, and its CSS and JS assets.
 *
 * The SlickSkin object has 3 supported methods: ::setSkins(), ::setDots(),
 * ::setArrows() to have skin options for main/thumbnail/overlay displays, dots,
 * and arrows skins respectively.
 * The declared skins will be available for custom coded, or UI selections.
 *
 * @see \Drupal\slick\SlickSkinPluginInterface
 * @see \Drupal\slick_example\Plugin\slick\SlickExampleSkin
 * @see \Drupal\slick_extras\Plugin\slick\SlickExtrasSkin
 * @see \Drupal\slick_test\Plugin\slick\SlickSkin for the most complete samples
 *
 * Add the needed methods accordingly.
 * This can be used to register skins for the Slick. Skins will be
 * available when configuring the Optionset, Field formatter, or Views style,
 * or custom coded slicks.
 *
 * Slick skins get a unique CSS class to use for styling, e.g.:
 * If your skin name is "my_module_slick_carousel_rounded", the CSS class is:
 * slick--skin--my-module-slick-carousel-rounded
 *
 * A skin can specify CSS and JS files to include when Slick is displayed,
 * except for a thumbnail skin which accepts CSS only.
 *
 * Each skin supports a few keys:
 * - name: The human readable name of the skin.
 * - description: The description about the skin, for help and manage pages.
 * - css: An array of CSS files to attach.
 * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
 * - group: A string grouping the current skin: main, thumbnail, arrows, dots.
 * - dependencies: Similar to how core library dependencies constructed.
 * - provider: A module name registering the skins.
 * - options: Extra JavaScript (Slicebox, 3d carousel, etc) options merged into
 *     existing [data-slick] attribute to be consumed by custom JS.
 *
 * @section sec_skins Defines the Slick main and thumbnail skins
 *
 * @code
 * protected function setSkins() {
 *   // If you copy this file, be sure to add base_path() before any asset path
 *   // (css or js) as otherwise failing to load the assets. Your module can
 *   // register paths pointing to a theme. Almost similar to library.
 *   $theme_path = base_path() . drupal_get_path('theme', 'my_theme');
 *
 *   return [
 *     'skin_name' => [
 *       // Human readable skin name.
 *       'name' => 'Skin name',
 *
 *       // Description of the skin.
 *       'description' => $this->t('Skin description.'),
 *
 *       // Group skins to reduce confusion on form selection: main, thumbnail.
 *       'group' => 'main',
 *
 *       // Optional module name to prefix the library name.
 *       'provider' => 'my_module',
 *
 *       // Custom assets to be included within a skin, e.g.: Zoom, Reflection,
 *       // Slicebox, etc.
 *       'css' => [
 *         'theme' => [
 *           // Full path to a CSS file to include with the skin.
 *           $theme_path . '/css/my-theme--slider.css' => [],
 *           $theme_path . '/css/my-theme--carousel.css' => [],
 *         ],
 *       ],
 *       'js' => [
 *         // Full path to a JS file to include with the skin.
 *         $theme_path . '/js/my-theme--slider.js' => [],
 *         $theme_path . '/js/my-theme--carousel.js' => [],
 *         // To act on afterSlick event, or any other slick events,
 *         // put a lighter weight before slick.load.min.js (0).
 *         $theme_path . '/js/slick.skin.menu.min.js' => ['weight' => -2],
 *       ],
 *
 *       // Alternatively, add extra library dependencies for re-usable
 *       // libraries. These must be registered as module libraries first.
 *       // Use above CSS and JS directly if reluctant to register libraries.
 *       'dependencies' => [
 *         'my_module/d3',
 *         'my_module/slicebox',
 *         'my_module/zoom',
 *       ],
 *
 *       // Add custom options to be merged into [data-slick] attribute.
 *       // Below is a sample of Slicebox options merged into Slick options.
 *       // These options later can be accessed in the custom JS acccordingly.
 *       'options' => [
 *         'orientation'     => 'r',
 *         'cuboidsCount'    => 7,
 *         'maxCuboidsCount' => 7,
 *         'cuboidsRandom'   => TRUE,
 *         'disperseFactor'  => 30,
 *         'itemAnimation'   => TRUE,
 *         'perspective'     => 1300,
 *         'reflection'      => TRUE,
 *         'effect'          => ['slicebox', 'zoom'],
 *       ],
 *     ],
 *   ];
 * }
 * @endcode
 *
 * @section sec_dots Defines Slick dot skins
 *
 * The provided dot skins will be available at sub-module UI form.
 * A skin dot named 'hop' will have a class 'slick-dots--hop' for the UL.
 *
 * The array is similar to the self::setSkins(), excluding group, JS.
 * @code
 * protected function setDots() {
 *   // Create an array of dot skins.
 *   return [];
 * }
 * @endcode
 *
 * @section sec_arrows Defines Slick arrow skins
 *
 * The provided arrow skins will be available at sub-module UI form.
 * A skin arrow 'slit' will have a class 'slick__arrow--slit' for the NAV.
 *
 * The array is similar to the self::setSkins(), excluding group, JS.
 *
 * @return array
 *   The array of the arrow skins.
 * @code
 * protected function setArrows() {
 *   // Create an array of arrow skins.
 *   return [];
 * }
 * @endcode
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modifies overridable options at admin UI to re-use one optionset.
 *
 * Only accepts boolean values as these are displayed as checkboxes under
 * `Override main optionset` form field at Slick formatter/ Slick Views forms.
 *
 * @see \Drupal\slick\Form\SlickAdmin::getOverridableOptions()
 * @see config/install/slick.optionset.default.yml
 *
 * @ingroup slick_api
 */
function hook_slick_overridable_options_info_alter(&$options) {
  // Adds RTL option to Slick field formatters, or Slick Views UI forms.
  $options['rtl'] = t('RTL');
}

/**
 * Modifies Slick optionset before being passed to preprocess, or templates.
 *
 * @param \Drupal\slick\Entity\Slick $slick
 *   The Slick object being modified.
 * @param array $settings
 *   The contextual settings related to UI and HTML layout settings.
 *
 * @see \Drupal\slick\SlickManager::preRenderSlick()
 *
 * @ingroup slick_api
 */
function hook_slick_optionset_alter(Slick &$slick, array $settings) {
  if ($slick->id() == 'x_slick_nav') {
    // Overrides the main settings of navigation with optionset ID x_slick_nav.
    // To see available options, see config/install/slick.optionset.default.yml.
    // Disable arrows.
    $slick->setSetting('arrows', FALSE);

    // Checks if we have defined responsive settings.
    if ($responsives = $slick->getResponsiveOptions()) {
      foreach ($responsives as $key => $responsive) {
        if ($responsive['breakpoint'] == 481) {
          // If Optimized option is enabled, only those different from default
          // settings will be displayed at $responsive array. To poke around
          // available settings, see config/install/slick.optionset.default.yml
          // See what we have here.
          // dpr($responsive);
          // Overrides responsive settings.
          $values = $responsive['settings'];
          $values['centerPadding'] = '40px';
          $values['slidesToShow'] = 1;

          // Assign the new settings values.
          $slick->setResponsiveSettings($values, $key);
          // Verify responsive settings updated.
          // dpr($slick->getResponsiveOptions());
        }
      }
    }
  }
}

/**
 * Modifies Slick HTML settings before being passed to preprocess, or templates.
 *
 * If you need to override globally to be inherited by all blazy-related
 * modules: slick, gridstack, mason, etc., consider hook_blazy_settings_alter().
 *
 * @param array $build
 *   The array containing: item, content, settings, or optional captions.
 * @param object $items
 *   The \Drupal\Core\Field\FieldItemListInterface items.
 *
 * @see \Drupal\blazy\BlazyFormatterManager::buildSettings()
 * @see \Drupal\slick\SlickFormatter::buildSettings()
 *
 * @ingroup slick_api
 */
function hook_slick_settings_alter(array &$build, $items) {
  $settings = &$build['settings'];

  // See blazy_blazy_settings_alter() at blazy.module for existing samples.
  // First check the $settings array. Slick Views may have different array.
  if (isset($settings['entity_id'])) {
    // Change skin if meeting a particular criteria.
    if ($settings['optionset'] == 'x_slick_for') {
      $settings['skin'] = $settings['entity_id'] == 54 ? 'fullwidth' : $settings['skin'];
    }

    // Swap optionset at particular pages.
    if (in_array($settings['entity_id'], [54, 64, 74])) {
      $settings['optionset'] == 'my_slick_pages';
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */

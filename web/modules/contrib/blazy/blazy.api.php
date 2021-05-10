<?php

/**
 * @file
 * Hooks and API provided by the Blazy module.
 */

/**
 * @defgroup blazy_api Blazy API
 * @{
 * Information about the Blazy usages.
 *
 * Modules may implement any of the available hooks to interact with Blazy.
 * Blazy may be configured using the web interface using formatters, or Views.
 * However below is a few sample coded ones.
 *
 * A single image sample.
 * @code
 * function my_module_render_blazy() {
 *   $settings = [
 *     // URI is required to use BlazyManager::getBlazy().
 *     // URI is stored in #settings property so to allow traveling around video
 *     // and lightboxes before being passed into theme_blazy().
 *     'uri' => 'public://logo.jpg',
 *
 *     // Explicitly request for Blazy.
 *     // This allows Slick lazyLoad to not load Blazy.
 *     'lazy' => 'blazy',
 *
 *     // Optionally provide an image style. Valid URI is a must:
 *     'image_style' => 'thumbnail',
 *   ];
 *
 *   $build = [
 *     '#theme'    => 'blazy',
 *     '#settings' => $settings,
 *
 *     // Or below for clarity:
 *     '#settings' => ['uri' => 'public://logo.jpg', 'lazy' => 'blazy'],
 *
 *     // Pass custom attributes into the same #item_attributes property as
 *     // Blazy formatters so to respect external modules like RDF, etc. without
 *     // extra property. The regular #attributes property is reserved by Blazy
 *     // container which holds either IMG, icons, or iFrame. Meaning Blazy is
 *     // not just IMG.
 *     '#item_attributes' => [
 *       'alt'   => t('Thumbnail'),
 *       'title' => t('Thumbnail title'),
 *       'width' => 120,
 *     ],
 *
 *     // Finally load the library, or include it into a parent container.
 *     '#attached' => ['library' => ['blazy/load']],
 *   ];
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Blazy::preprocessBlazy()
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 *
 * A multiple image sample.
 *
 * For advanced usages with multiple images, and a few Blazy features such as
 * lightboxes, lazyloaded images, or iframes, including CSS background and
 * aspect ratio, etc.:
 *   o Invoke blazy.manager, and or blazy.formatter, services.
 *   o Use \Drupal\blazy\BlazyManager::getBlazy() method to work with images and
 *     pass relevant settings which request for particular Blazy features
 *     accordingly.
 *   o Use \Drupal\blazy\BlazyManager::attach() to load relevant libraries.
 * @code
 * function my_module_render_blazy_multiple() {
 *   // Invoke the plugin class, or use a DI service container accordingly.
 *   $manager = \Drupal::service('blazy.manager');
 *
 *   $settings = [
 *     // Explicitly request for Blazy library.
 *     // This allows Slick lazyLoad, or text formatter, to not load Blazy.
 *     'blazy' => TRUE,
 *
 *     // Supported media switcher options dependent on available modules:
 *     // colorbox, media (Image to iframe), photobox.
 *     'media_switch' => 'media',
 *   ];
 *
 *   // Build images.
 *   $build = [
 *     // Load images via $manager->getBlazy().
 *     // See below ...Formatter::buildElements() for consistent samples.
 *   ];
 *
 *   // Finally attach libraries as requested via $settings.
 *   $build['#attached'] = $manager->attach($settings);
 *
 *   return $build;
 * }
 * @endcode
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyFormatterBlazy::buildElements()
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatterBase::buildElements()
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickFileFormatterBase::buildElements()
 * @see \Drupal\blazy\BlazyManager::getBlazy()
 * @see \Drupal\blazy\BlazyDefault::imageSettings()
 *
 *
 * Pre-render callback sample to modify/ extend Blazy output.
 * @code
 * function my_module_pre_render(array $image) {
 *   $settings = isset($image['#settings']) ? $image['#settings'] : [];
 *
 *   // Video's HREF points to external site, adds URL to local image.
 *   if (!empty($settings['box_url']) && !empty($settings['embed_url'])) {
 *     $image['#url_attributes']['data-box-url'] = $settings['box_url'];
 *   }
 *
 *   return $image;
 * }
 * @endcode
 * @see hook_blazy_alter()
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters Blazy attachments to add own library, drupalSettings, and JS template.
 *
 * @param array $load
 *   The array of loaded library being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_attach_alter(array &$load, array $settings = []) {
  if (!empty($settings['photoswipe'])) {
    $load['library'][] = 'my_module/load';

    $template = ['#theme' => 'photoswipe_container'];
    $load['drupalSettings']['photoswipe'] = [
      'options' => blazy()->configLoad('options', 'photoswipe.settings'),
      'container' => blazy()->getRenderer()->renderPlain($template),
    ];
  }
}

/**
 * Alters available lightboxes for Media switch select option at Blazy UI.
 *
 * @param array $lightboxes
 *   The array of lightbox options being modified.
 *
 * @see https://www.drupal.org/project/blazy_photoswipe
 *
 * @ingroup blazy_api
 */
function hook_blazy_lightboxes_alter(array &$lightboxes) {
  $lightboxes[] = 'photoswipe';
}

/**
 * Alters Blazy individual item output to support a custom lightbox.
 *
 * @param array $build
 *   The renderable array of image/ video iframe being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_alter(array &$build, array $settings = []) {
  if (!empty($settings['media_switch']) && $settings['media_switch'] == 'photoswipe') {
    $build['#pre_render'][] = 'my_module_pre_render';
  }
}

/**
 * Alters Blazy outputs entirely to support a custom (quasy-)lightbox.
 *
 * In a case of ElevateZoom Plus, it adds a prefix large image preview before
 * the Blazy Grid elements by adding an extra #theme_wrappers via #pre_render
 * element.
 *
 * @param array $build
 *   The renderable array of the entire Blazy output being modified.
 * @param array $settings
 *   The available array of settings.
 *
 * @ingroup blazy_api
 */
function hook_blazy_build_alter(array &$build, array $settings = []) {
  if (!empty($settings['elevatezoomplus'])) {
    $build['#pre_render'][] = 'my_module_pre_render_build';
  }
}

/**
 * Alters blazy-related formatter form options to make site-builders happier.
 *
 * A less robust alternative to third party settings to pass the options to
 * blazy-related formatters within the designated compact form.
 * While third party settings offer more fine-grained control over a specific
 * formatter, this offers a swap to various blazy-related formatters at one go.
 * Any class extending \Drupal\blazy\BlazyDefault will be capable
 * to modify both form and UI options at one go.
 *
 * This requires 4 things: option definitions (this alter), schema, extended
 * forms, and front-end implementation of the provided options which can be done
 * via regular hook_preprocess().
 *
 * Accordingly update the schema via core hook_config_schema_info_alter(), or
 * regular module.schema.yml file to have a valid schema.
 * @code
 * function hook_config_schema_info_alter(array &$definitions) {
 *   $settings = ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
 *   BlazyAlter::configSchemaInfoAlter($definitions,
 *     'slick_base', SlickDefault::extendedSettings() + $settings);
 * }
 * @endcode
 *
 * In addition to the schema, implement hook_blazy_complete_form_element_alter()
 * to provide the actual extended forms, see far below. And lastly, implement
 * the options at front-end via hook_preprocess().
 *
 * @param array $settings
 *   The settings being modified.
 * @param array $context
 *   The array containing class which defines or limit the scope of the options.
 *
 * @ingroup blazy_api
 */
function hook_blazy_base_settings_alter(array &$settings, array $context = []) {
  // One override for both various Slick field formatters and Slick views style.
  // SlickDefault extends BlazyDefault, hence capable to modify/ extend options.
  // These options will be available at many Slick formatters at one go.
  if ($context['class'] == 'Drupal\slick\SlickDefault') {
    $settings += ['color' => '', 'arrowpos' => '', 'dotpos' => ''];
  }
}

/**
 * Alters blazy settings inherited by all child elements.
 *
 * @param array $build
 *   The array containing: settings, or potential optionset for extensions.
 * @param object $items
 *   The Drupal\Core\Field\FieldItemListInterface items.
 *
 * @ingroup blazy_api
 */
function hook_blazy_settings_alter(array &$build, $items) {
  $settings = &$build['settings'];

  // Overrides one pixel placeholder on particular pages relevant if using Views
  // rewrite results which may strip out Data URI.
  // See https://drupal.org/node/2908861.
  if (isset($settings['entity_id'])
    && in_array($settings['entity_id'], [45, 67])) {
    $settings['placeholder'] = '/blank.gif';
  }

  // Alternatively override views blocks identified by `current_view_mode` with
  // a blank SVG since 1px gif has issues with non-square sizes, see #2908861:
  // <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'/>
  // Adjust plugin ID since Blazy has few formatters, not Views style:
  // blazy for plain old Image, blazy_media for Media, blazy_oembed for oEmbed.
  $blazy = isset($settings['plugin_id']) && $settings['plugin_id'] == 'blazy_media';
  $rewriten = ['block_categories', 'block_popular', 'block_related'];
  if ($blazy && isset($settings['current_view_mode']) && in_array($settings['current_view_mode'], $rewriten)) {
    $settings['placeholder'] = '/blank.svg';
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * This takes advantage of Blazy taking care of a few elements finalizations,
 * such as adding #empty_option, extras CSS classes, checkboxes, states, etc.
 * This is run before hook_blazy_complete_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_form_element_alter(array &$form, array $definition = []) {
  // Limit the scope to Slick formatters, blazy, gridstack, etc. Or swap em all.
  if (isset($definition['namespace']) && $definition['namespace'] == 'slick') {
    // Extend the formatter form elements as needed.
  }
}

/**
 * Alters blazy-related formatter form elements.
 *
 * Modify anything Blazy forms output as you wish.
 * This is run after hook_blazy_form_element_alter().
 *
 * @param array $form
 *   The $form being modified.
 * @param array $definition
 *   The array defining the scope of form elements.
 *
 * @see \Drupal\blazy\Form\BlazyAdminBase::finalizeForm()
 *
 * @ingroup blazy_api
 */
function hook_blazy_complete_form_element_alter(array &$form, array $definition = []) {
  // Limit the scope to Slick formatters, blazy, gridstack, etc. Or swap em all.
  if (isset($definition['namespace']) && $definition['namespace'] == 'slick') {
    // Extend the formatter form elements as needed.
  }
}

/**
 * @} End of "addtogroup hooks".
 */

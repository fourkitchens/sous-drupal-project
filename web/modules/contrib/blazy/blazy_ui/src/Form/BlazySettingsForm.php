<?php

namespace Drupal\blazy_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines blazy admin settings form.
 */
class BlazySettingsForm extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\blazy_ui\Form\BlazySettingsForm
     */
    $instance = parent::create($container);
    $instance->libraryDiscovery = $container->get('library.discovery');
    $instance->manager = $container->get('blazy.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blazy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['blazy.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('blazy.settings');

    $form['admin_css'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Admin CSS'),
      '#default_value' => $config->get('admin_css'),
      '#description'   => $this->t('Uncheck to disable blazy related admin compact form styling, only if not compatible with your admin theme.'),
    ];

    $form['native'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Native browser lazy load'),
      '#default_value' => $config->get('native'),
      '#description'   => $this->t('Native lazy loading is supported by Chrome 76+ as of 01/2019, and Firefox 76+ 5/2020. Blazy or IO will be used as fallback for other browsers instead. If enabled, Blur effect, preloader animation, image transition, and other animations, or other fancy features which depend on visibility delays, may no longer work for the below-fold contents till we have a nicer integration. This also may trick us to think lazy load not work, check out Blazy docs or project issues for better explanations.'),
    ];

    $form['noscript'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Add noscript'),
      '#default_value' => $config->get('noscript'),
      '#description'   => $this->t('Enable noscript if you want to support <a href=":url">non-javascript users</a>.', [':url' => 'https://stackoverflow.com/questions/9478737']),
    ];

    $form['responsive_image'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Support Responsive image'),
      '#default_value' => $config->get('responsive_image'),
      '#description'   => $this->t('Check to support lazyloading for the core Responsive image module. Be sure to use blazy-related formatters.'),
      '#disabled'      => !function_exists('responsive_image_get_image_dimensions'),
    ];

    $form['one_pixel'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Responsive image 1px placeholder'),
      '#default_value' => $config->get('one_pixel'),
      '#description'   => $this->t('By default a 1px Data URI image is the placeholder for lazyloaded Responsive image. Useful to perform a lot better. Uncheck to disable, and use Drupal-managed smallest/fallback image style instead. Or keep it checked, and override it per <b>Image style</b> option at blazy-related formatters instead. Be sure to add proper dimensions or at least min-height/min-width via CSS accordingly to avoid layout reflow, or choose an Aspect ratio via Blazy formatters. Disabling this will result in downloading fallback image as well for non-PICTURE element (double downloads).'),
    ];

    $form['placeholder'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Placeholder'),
      '#default_value' => $config->get('placeholder'),
      '#description'   => $this->t("Overrides global 1px placeholder. Can be URL, e.g.: /blank.gif or /blank.svg. Be warned: unlike .svg, browsers have display issues with 1px .gif, see <a href=':url1'>#2795415</a>. Only useful if continuously using Views rewrite results, see <a href=':url2'>#2908861</a>. Alternatively use <code>hook_blazy_settings_alter()</code> for more fine-grained control. Leave it empty to use default inline SVG or Data URI to avoid extra HTTP requests. If you have 100 images on a page, you will save 100 extra HTTP requests by leaving it empty. The <b>blank.svg</b> content sample if not using blank.gif: <br><code>&lt;svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'/&gt;</code>", [
        ':url1' => 'https://drupal.org/node/2795415',
        ':url2' => 'https://drupal.org/node/2908861',
      ]),
    ];

    $form['unstyled_extensions'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Extensions without image styles'),
      '#default_value' => $config->get('unstyled_extensions'),
      '#description'   => $this->t('Extensions that should not use (Responsive) image style, space delimited without dot, e.g.: <code>gif apng</code> <br>Normally animated images. No way to distinguish animated from static gif, it is all or nothing. This means no thumbnail, no blur, nor features which makes use image style. Default to svg.'),
    ];

    $form['fx'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Image effect'),
      '#empty_option'  => '- None -',
      '#options'       => $this->manager->getImageEffects(),
      '#default_value' => $config->get('fx'),
      '#description'   => $this->t('Choose the image effect. Will use Thumbnail style option at Blazy formatters for the placeholder with fallback to core Thumbnail style. For best results: use similar aspect ratio for both Thumbnail and Image styles; adjust Offset and or threshold; the smaller the better. Use <code>hook_blazy_image_effects_alter()</code> to add more effects -- curtain, fractal, slice, whatever. <b>Limitations</b>: Best with a proper Aspect ratio option as otherwise collapsed image. Be sure to add one. If not, add regular CSS <code>min-height</code> for each mediaquery. The Placeholder option is still respected.'),
    ];

    $form['blazy'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Blazy settings'),
      '#description' => $this->t('The following settings are related to Blazy library.'),
    ];

    $form['blazy']['loadInvisible'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Load invisible'),
      '#default_value' => $config->get('blazy.loadInvisible'),
      '#description'   => $this->t('Check if you want to load invisible (hidden) elements.'),
    ];

    $form['blazy']['offset'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Offset'),
      '#default_value' => $config->get('blazy.offset'),
      '#description'   => $this->t("The offset controls how early you want the elements to be loaded before they're visible. Default is <strong>100</strong>, so 100px before an element is visible it'll start loading."),
      '#field_suffix'  => 'px',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['saveViewportOffsetDelay'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Save viewport offset delay'),
      '#default_value' => $config->get('blazy.saveViewportOffsetDelay'),
      '#description'   => $this->t('Delay for how often it should call the saveViewportOffset function on resize. Default is <strong>50</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['validateDelay'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Set validate delay'),
      '#default_value' => $config->get('blazy.validateDelay'),
      '#description'   => $this->t('Delay for how often it should call the validate function on scroll/resize. Default is <strong>25</strong>ms.'),
      '#field_suffix'  => 'ms',
      '#maxlength'     => 5,
      '#size'          => 10,
    ];

    $form['blazy']['container'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Scrolling container'),
      '#default_value' => $config->get('blazy.container'),
      '#description'   => $this->t('If you put Blazy within a scrolling container, provide valid comma separated CSS selectors, except <code>#drupal-modal, .is-b-scroll</code>, e.g.: <code>#my-scrolling-container, .another-scrolling-container</code>. Known scrolling containers are <code>#drupal-modal</code> like seen at Media library, parallax containers with fixed height replacing default browser scrollbar. A scrolling modal with an iframe like Entity Browser has no issue since the scrolling container is the entire DOM. Must know <code>.blazy</code> parent container, or itself, which has CSS rules containing <code>overflow</code> with values anything but <code>hidden</code> such as <code>auto or scroll</code>. Press <code>F12</code> at any browser to inspect elements. IO does not need it, old bLazy does. Default to known <code>#drupal-modal, .is-b-scroll</code>. The <code>.is-b-scroll</code> is for modules which cannot reach this UI without extra legs. Symptons: eternal blue loader while should be loaded.'),
    ];

    $form['io'] = [
      '#type'        => 'details',
      '#tree'        => TRUE,
      '#open'        => TRUE,
      '#title'       => $this->t('Intersection Observer API (IO) settings (<b>Experimental!</b>)'),
      '#description' => $this->t('The following settings are related to <a href=":url">IntersectionObserver API</a>.', [':url' => 'https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API']),
    ];

    $form['io']['enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable IO API'),
      '#default_value' => $config->get('io.enabled'),
      '#description'   => $this->t('Check if you want to use IO API for modern browsers, and Blazy for oldies.'),
    ];

    $form['io']['unblazy'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Unload bLazy'),
      '#default_value' => $config->get('io.unblazy'),
      '#description'   => $this->t("Check if you are happy with IO. This will not load the original bLazy library, no fallback. Watch out for JS errors at browser consoles, and uncheck if any, or unsure. Blazy is just ~1KB gzip. Clear caches!"),
    ];

    $form['io']['rootMargin'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('rootMargin'),
      '#default_value' => $config->get('io.rootMargin') ?: '0px',
      '#description'   => $this->t("Margin around the root. Can have values similar to the CSS margin property, e.g. <code>10px 20px 30px 40px</code> (top, right, bottom, left). The values can be percentages. This set of values serves to grow or shrink each side of the root element's bounding box before computing intersections. Defaults to all zeros."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['threshold'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('threshold'),
      '#default_value' => $config->get('io.threshold') ?: '0',
      '#description'   => $this->t("Either a single number or an array of numbers which indicate at what percentage of the target's visibility the observer's callback should be executed. If you only want to detect when visibility passes the 50% mark, you can use a value of 0.5. If you want the callback to run every time visibility passes another 25%, you would specify the array [<code>0, 0.25, 0.5, 0.75, 1</code>] (without brackets). The default is 0 (meaning as soon as even one pixel is visible, the callback will be run). A value of 1.0 means that the threshold isn't considered passed until every pixel is visible."),
      '#maxlength'     => 120,
      '#size'          => 20,
    ];

    $form['io']['disconnect'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Disconnect'),
      '#default_value' => $config->get('io.disconnect'),
      '#description'   => $this->t('Check if you want to disconnect IO once all images loaded. If you keep seeing eternal blue loader while an image should be already loaded, this means it is not working yet in all cases. Just uncheck this.'),
    ];

    // Allows sub-modules to provide its own settings.
    $form['extras'] = [
      '#type'   => 'details',
      '#open'   => FALSE,
      '#tree'   => TRUE,
      '#title'  => $this->t('Extra settings'),
      '#access' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('blazy.settings');
    $config
      ->set('admin_css', $form_state->getValue('admin_css'))
      ->set('fx', $form_state->getValue('fx'))
      ->set('native', $form_state->getValue('native'))
      ->set('noscript', $form_state->getValue('noscript'))
      ->set('responsive_image', $form_state->getValue('responsive_image'))
      ->set('one_pixel', $form_state->getValue('one_pixel'))
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->set('unstyled_extensions', $form_state->getValue('unstyled_extensions'))
      ->set('blazy.loadInvisible', $form_state->getValue([
        'blazy',
        'loadInvisible',
      ]))
      ->set('blazy.offset', $form_state->getValue(['blazy', 'offset']))
      ->set('blazy.saveViewportOffsetDelay', $form_state->getValue([
        'blazy',
        'saveViewportOffsetDelay',
      ]))
      ->set('blazy.validateDelay', $form_state->getValue([
        'blazy',
        'validateDelay',
      ]))
      ->set('blazy.container', $form_state->getValue(['blazy', 'container']))
      ->set('io.enabled', $form_state->getValue(['io', 'enabled']))
      ->set('io.unblazy', $form_state->getValue(['io', 'unblazy']))
      ->set('io.rootMargin', $form_state->getValue(['io', 'rootMargin']))
      ->set('io.threshold', $form_state->getValue(['io', 'threshold']))
      ->set('io.disconnect', $form_state->getValue(['io', 'disconnect']));

    if ($form_state->hasValue('extras')) {
      foreach ($form_state->getValue('extras') as $key => $value) {
        $config->set('extras.' . $key, $value);
      }
    }

    $config->save();

    // Invalidate the library discovery cache to update the responsive image.
    $this->libraryDiscovery->clearCachedDefinitions();
    $this->configFactory->clearStaticCache();

    $this->messenger()->addMessage($this->t('Be sure to <a href=":clear_cache">clear the cache</a> if trouble to see the updated settings.', [':clear_cache' => Url::fromRoute('system.performance_settings')->toString()]));

    parent::submitForm($form, $form_state);
  }

}

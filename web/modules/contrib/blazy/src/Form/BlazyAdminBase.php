<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyManagerInterface;

/**
 * A base for blazy admin integration to have re-usable methods in one place.
 *
 * @see \Drupal\gridstack\Form\GridStackAdmin
 * @see \Drupal\mason\Form\MasonAdmin
 * @see \Drupal\slick\Form\SlickAdmin
 * @see \Drupal\blazy\Form\BlazyAdminFormatterBase
 */
abstract class BlazyAdminBase implements BlazyAdminInterface {

  use StringTranslationTrait;

  /**
   * A state that represents the responsive image style is disabled.
   */
  const STATE_RESPONSIVE_IMAGE_STYLE_DISABLED = 0;

  /**
   * A state that represents the media switch lightbox is enabled.
   */
  const STATE_LIGHTBOX_ENABLED = 1;

  /**
   * A state that represents the media switch iframe is enabled.
   */
  const STATE_IFRAME_ENABLED = 2;

  /**
   * A state that represents the thumbnail style is enabled.
   */
  const STATE_THUMBNAIL_STYLE_ENABLED = 3;

  /**
   * A state that represents the custom lightbox caption is enabled.
   */
  const STATE_LIGHTBOX_CUSTOM = 4;

  /**
   * A state that represents the image rendered switch is enabled.
   */
  const STATE_IMAGE_RENDERED_ENABLED = 5;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The typed config manager service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyAdminBase object.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\slick\BlazyManagerInterface $blazy_manager
   *   The blazy manager service.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository, TypedConfigManagerInterface $typed_config, DateFormatterInterface $date_formatter, BlazyManagerInterface $blazy_manager) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->typedConfig             = $typed_config;
    $this->dateFormatter           = $date_formatter;
    $this->blazyManager            = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_display.repository'), $container->get('config.typed'), $container->get('date.formatter'), $container->get('blazy.manager'));
  }

  /**
   * Returns the entity display repository.
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository;
  }

  /**
   * Returns the typed config.
   */
  public function getTypedConfig() {
    return $this->typedConfig;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Returns shared form elements across field formatter and Views.
   */
  public function openingForm(array &$form, &$definition = []) {
    $this->blazyManager->getModuleHandler()->alter('blazy_form_element_definition', $definition);

    // Display style: column, plain static grid, slick grid, slick carousel.
    // https://drafts.csswg.org/css-multicol
    if (!empty($definition['style'])) {
      $form['style'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Display style'),
        '#description'  => $this->t('Either <strong>CSS3 Columns</strong> (experimental pure CSS Masonry) or <strong>Grid Foundation</strong> requires <strong>Grid</strong>. Difference: <strong>Columns</strong> is best with irregular image sizes (scale width, empty height), affects the natural order of grid items. <strong>Grid</strong> with regular cropped ones. Unless required, leave empty to use default formatter, or style.'),
        '#enforced'     => TRUE,
        '#empty_option' => '- None -',
        '#options'      => [
          'column' => $this->t('CSS3 Columns'),
          'grid'   => $this->t('Grid Foundation'),
        ],
        '#required' => !empty($definition['grid_required']),
        '#weight'   => -112,
        '#wrapper_attributes' => [
          'class' => [
            'form-item--style',
            'form-item--tooltip-bottom',
          ],
        ],
      ];
    }

    if (!empty($definition['skins'])) {
      $form['skin'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin'),
        '#options'     => $definition['skins'],
        '#enforced'    => TRUE,
        '#description' => $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use the provided hook_info() and implement the skin interface to register ones.'),
        '#weight'      => -107,
      ];
    }

    if (!empty($definition['background'])) {
      $form['background'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Use CSS background'),
        '#description' => $this->t('Check this to turn the image into CSS background. This opens up the goodness of CSS, such as background cover, fixed attachment, etc. <br /><strong>Important!</strong> Requires an Aspect ratio, otherwise collapsed containers. Unless explicitly removed such as for GridStack which manages its own problem, or a min-height is added manually to <strong>.media--background</strong> selector.'),
        '#weight'      => -98,
      ];
    }

    if (!empty($definition['layouts'])) {
      $form['layout'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Layout'),
        '#options'     => $definition['layouts'],
        '#description' => $this->t('Requires a skin. The builtin layouts affects the entire items uniformly. Leave empty to DIY.'),
        '#weight'      => 2,
      ];
    }

    if (!empty($definition['captions'])) {
      $form['caption'] = [
        '#type'        => 'checkboxes',
        '#title'       => $this->t('Caption fields'),
        '#options'     => $definition['captions'],
        '#description' => $this->t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.'),
        '#weight'      => 80,
        '#attributes'  => ['class' => ['form-wrapper--caption']],
      ];
    }

    if (!empty($definition['target_type']) && !empty($definition['view_mode'])) {
      $form['view_mode'] = $this->baseForm($definition)['view_mode'];
    }

    $weight = -99;
    foreach (Element::children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Returns re-usable grid elements across field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    $range = range(1, 12);
    $grid_options = array_combine($range, $range);
    $required = !empty($definition['grid_required']);

    $header = $this->t('Group individual items as block grid<small>Depends on the <strong>Display style</strong>.</small>');
    $form['grid_header'] = [
      '#type'   => 'markup',
      '#markup' => '<h3 class="form__title form__title--grid">' . $header . '</h3>',
      '#access' => !$required,
    ];

    if ($required) {
      $description = $this->t('The amount of block grid columns for large monitors 64.063em.');
    }
    else {
      $description = $this->t('Select <strong>- None -</strong> first if trouble with changing form states. The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents.</li></ol>Unless required, leave empty to DIY, or to not build grids.');
    }
    $form['grid'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid large'),
      '#options'     => $grid_options,
      '#description' => $description,
      '#enforced'    => TRUE,
      '#required'    => $required,
    ];

    $form['grid_medium'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid medium'),
      '#options'     => $grid_options,
      '#description' => $this->t('The amount of block grid columns for medium devices 40.063em - 64em.'),
    ];

    $form['grid_small'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid small'),
      '#options'     => $grid_options,
      '#description' => $this->t('The amount of block grid columns for small devices 0 - 40em. Specific to <strong>CSS3 Columns</strong>, only 1 - 2 column is respected due to small real estate at smallest device.'),
    ];

    $form['visible_items'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Visible items'),
      '#options'     => array_combine(range(1, 32), range(1, 32)),
      '#description' => $this->t('How many items per display at a time.'),
    ];

    $form['preserve_keys'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Preserve keys'),
      '#description' => $this->t('If checked, keys will be preserved. Default is FALSE which will reindex the grid chunk numerically.'),
      '#access'      => FALSE,
    ];

    $grids = [
      'grid_header',
      'grid_medium',
      'grid_small',
      'visible_items',
      'preserve_keys',
    ];

    foreach ($grids as $key) {
      $form[$key]['#enforced'] = TRUE;
      $form[$key]['#states'] = [
        'visible' => [
          'select[name$="[grid]"]' => ['!value' => ''],
        ],
      ];
    }
  }

  /**
   * Returns shared ending form elements across field formatter and Views.
   */
  public function closingForm(array &$form, $definition = []) {
    if (isset($definition['current_view_mode'])) {
      $form['current_view_mode'] = [
        '#type'          => 'hidden',
        '#default_value' => isset($definition['current_view_mode']) ? $definition['current_view_mode'] : '_custom',
        '#weight'        => 120,
      ];
    }

    $this->finalizeForm($form, $definition);
  }

  /**
   * Returns simple form elements common for Views field, EB widget, formatters.
   */
  public function baseForm($definition = []) {
    $settings   = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes = $this->blazyManager->getLightboxes();
    $form       = [];
    $ui_url     = '/admin/config/media/blazy';

    if ($this->blazyManager->getModuleHandler()->moduleExists('blazy_ui')) {
      $ui_url = Url::fromRoute('blazy.settings')->toString();
    }

    if (empty($definition['no_image_style'])) {
      $form['image_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Image style'),
        '#options'     => $this->getEntityAsOptions('image_style'),
        '#description' => $this->t('The content image style. This will be treated as the fallback image to override the global option <a href=":url">Responsive image 1px placeholder</a>, which is normally smaller, if Responsive image are provided. Otherwise this is the only image displayed. This image style is also used to provide dimensions not only for image/iframe but also any media entity like local video, where no images are even associated with, to have the designated dimensions in tandem with aspect ratio as otherwise no UI to customize for.', [':url' => $ui_url]),
        '#weight'      => -100,
      ];
    }

    if (isset($settings['media_switch'])) {
      $form['media_switch'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Media switcher'),
        '#options'      => [
          'content' => $this->t('Image linked to content'),
        ],
        '#empty_option' => $this->t('- None -'),
        '#description'  => $this->t('May depend on the enabled supported or supportive modules: colorbox, photobox etc. Add Thumbnail style if using Photobox, Slick, or others which may need it. Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.'),
        '#weight'       => -99,
      ];

      // Optional lightbox integration.
      if (!empty($lightboxes)) {
        foreach ($lightboxes as $lightbox) {
          $name = Unicode::ucwords(str_replace('_', ' ', $lightbox));
          $form['media_switch']['#options'][$lightbox] = $this->t('Image to @lightbox', ['@lightbox' => $name]);
        }

        // Re-use the same image style for both lightboxes.
        $form['box_style'] = [
          '#type'    => 'select',
          '#title'   => $this->t('Lightbox image style'),
          '#options' => $this->getEntityAsOptions('image_style'),
          '#states'  => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#weight'  => -97,
        ];

        if (!empty($definition['multimedia'])) {
          $form['box_media_style'] = [
            '#type'        => 'select',
            '#title'       => $this->t('Lightbox video style'),
            '#options'     => $this->getEntityAsOptions('image_style'),
            '#description' => $this->t('Allows different lightbox video dimensions. Or can be used to have a swipable video if <a href=":url1">Blazy PhotoSwipe</a> or <a href=":url2">Slick Lightbox</a> installed.', [
              ':url1' => 'https:drupal.org/project/blazy_photoswipe',
              ':url2' => 'https:drupal.org/project/slick_lightbox',
            ]),
            '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
            '#weight'      => -96,
          ];
        }
      }

      // Adds common supported entities for media integration.
      if (!empty($definition['multimedia'])) {
        $form['media_switch']['#options']['media'] = $this->t('Image to iFrame');
      }

      // http://en.wikipedia.org/wiki/List_of_common_resolutions
      $ratio = ['1:1', '3:2', '4:3', '8:5', '16:9', 'fluid'];
      if (empty($definition['no_ratio'])) {
        $form['ratio'] = [
          '#type'         => 'select',
          '#title'        => $this->t('Aspect ratio'),
          '#options'      => array_combine($ratio, $ratio),
          '#empty_option' => $this->t('- None -'),
          '#description'  => $this->t('Aspect ratio to get consistently responsive images and iframes. Coupled with Image style. And to fix layout reflow and excessive height issues. <a href="@dimensions" target="_blank">Image styles and video dimensions</a> must <a href="@follow" target="_blank">follow the aspect ratio</a>. If not, images will be distorted. Use fixed ratio (non-fluid) to avoid JS works, or if it fails Responsive image. Fixed ratio means, all images from mobile to desktop use the same aspect ratio. Fluid means dimensions are calculated and JS works are attempted to fix aspect ratio. <a href="@link" target="_blank">Learn more</a>, or leave empty to DIY (such as using CSS mediaquery), or when working with multi-image-style plugin like GridStack.', [
            '@dimensions'  => '//size43.com/jqueryVideoTool.html',
            '@follow'      => '//en.wikipedia.org/wiki/Aspect_ratio_%28image%29',
            '@link'        => '//www.smashingmagazine.com/2014/02/27/making-embedded-content-work-in-responsive-design/',
          ]),
          '#weight'        => -95,
        ];
      }
    }

    if (!empty($definition['target_type']) && !empty($definition['view_mode'])) {
      $form['view_mode'] = [
        '#type'        => 'select',
        '#options'     => $this->getViewModeOptions($definition['target_type']),
        '#title'       => $this->t('View mode'),
        '#description' => $this->t('Required to grab the fields, or to have custom entity display as fallback display. If it has fields, be sure the selected "View mode" is enabled, and the enabled fields here are not hidden there.'),
        '#weight'      => -94,
        '#enforced'    => TRUE,
      ];

      if ($this->blazyManager->getModuleHandler()->moduleExists('field_ui')) {
        $form['view_mode']['#description'] .= ' ' . $this->t('Manage view modes on the <a href=":view_modes">View modes page</a>.', [':view_modes' => Url::fromRoute('entity.entity_view_mode.collection')->toString()]);
      }
    }

    if (!empty($definition['thumbnail_style'])) {
      $form['thumbnail_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail style'),
        '#options'     => $this->getEntityAsOptions('image_style'),
        '#description' => $this->t('Usages: Placeholder replacement for image effects (blur, etc.), Photobox/PhotoSwipe thumbnail, or custom work with thumbnails. Be sure to have similar aspect ratio for the best blur effect. Leave empty to not use thumbnails.'),
        '#weight'      => -96,
      ];
    }

    // @todo this can also be used for local video poster image option.
    if (isset($definition['images'])) {
      $form['image'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Main stage'),
        '#options'     => is_array($definition['images']) ? $definition['images'] : [],
        '#description' => $this->t('Main background/stage/poster image field with the only supported field types: <b>Image</b> or <b>Media</b> containing Image field. You may want to add a new Image field to this entity.'),
        '#prefix'      => '<h3 class="form__title form__title--fields">' . $this->t('Fields') . '</h3>',
      ];
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_base_form_element', $form, $definition);

    return $form;
  }

  /**
   * Returns re-usable media switch form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    $settings   = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes = $this->blazyManager->getLightboxes();
    $is_token   = $this->blazyManager->getModuleHandler()->moduleExists('token');

    if (isset($settings['media_switch'])) {
      $form['media_switch'] = $this->baseForm($definition)['media_switch'];
      $form['media_switch']['#prefix'] = '<h3 class="form__title form__title--media-switch">' . $this->t('Media switcher') . '</h3>';

      if (empty($definition['no_ratio'])) {
        $form['ratio'] = $this->baseForm($definition)['ratio'];
      }
    }

    // Optional lightbox integration.
    if (!empty($lightboxes) && isset($settings['media_switch'])) {
      $form['box_style'] = $this->baseForm($definition)['box_style'];

      if (!empty($definition['multimedia'])) {
        $form['box_media_style'] = $this->baseForm($definition)['box_media_style'];
      }

      $box_captions = [
        'auto'         => $this->t('Automatic'),
        'alt'          => $this->t('Alt text'),
        'title'        => $this->t('Title text'),
        'alt_title'    => $this->t('Alt and Title'),
        'title_alt'    => $this->t('Title and Alt'),
        'entity_title' => $this->t('Content title'),
        'custom'       => $this->t('Custom'),
      ];

      if (!empty($definition['box_captions'])) {
        $form['box_caption'] = [
          '#type'        => 'select',
          '#title'       => $this->t('Lightbox caption'),
          '#options'     => $box_captions,
          '#weight'      => -95,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#description' => $this->t('Automatic will search for Alt text first, then Title text. Try selecting <strong>- None -</strong> first when changing if trouble with form states.'),
        ];

        $form['box_caption_custom'] = [
          '#title'       => $this->t('Lightbox custom caption'),
          '#type'        => 'textfield',
          '#weight'      => -94,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_CUSTOM, $definition),
          '#description' => $this->t('Multi-value rich text field will be mapped to each image by its delta.'),
        ];

        if ($is_token) {
          $types = isset($definition['entity_type']) ? [$definition['entity_type']] : [];
          $types = isset($definition['target_type']) ? array_merge($types, [$definition['target_type']]) : $types;
          $form['box_caption_custom']['#field_suffix'] = [
            '#theme'       => 'token_tree_link',
            '#text'        => $this->t('Tokens'),
            '#token_types' => $types,
          ];
        }
      }
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_media_switch_form_element', $form, $definition);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $namespace = isset($definition['namespace']) ? $definition['namespace'] : 'slick';
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $vanilla = !empty($definition['vanilla']) ? ' form--vanilla' : '';
    $grid = !empty($definition['grid_required']) ? ' form--grid-required' : '';
    $plugind_id = !empty($definition['plugin_id']) ? ' form--plugin-' . str_replace('_', '-', $definition['plugin_id']) : '';
    $count = empty($definition['captions']) ? 0 : count($definition['captions']);
    $count = empty($definition['captions_count']) ? $count : $definition['captions_count'];
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;
    $fallback = $namespace == 'slick' ? 'form--slick' : 'form--' . $namespace . ' form--slick';
    $plugins = ' form--namespace-' . $namespace;
    $custom = isset($definition['opening_class']) ? ' ' . $definition['opening_class'] : '';
    // @todo remove form_opening_classes for opening_class.
    $classes = isset($definition['form_opening_classes'])
      ? $definition['form_opening_classes']
      : $fallback . ' form--half has-tooltip' . $wide . $vanilla . $grid . $plugind_id . $custom . $plugins;

    if (!empty($definition['field_type'])) {
      $classes .= ' form--' . str_replace('_', '-', $definition['field_type']);
    }

    $form['opening'] = [
      '#markup' => '<div class="' . $classes . '">',
      '#weight' => -120,
    ];

    $form['closing'] = [
      '#markup' => '</div>',
      '#weight' => 120,
    ];

    // @todo: Check if needed: 'button', 'container', 'submit'.
    $admin_css = isset($definition['admin_css']) ? $definition['admin_css'] : '';
    $admin_css = $admin_css ?: $this->blazyManager->configLoad('admin_css', 'blazy.settings');
    $excludes  = ['details', 'fieldset', 'hidden', 'markup', 'item', 'table'];
    $selects   = ['cache', 'optionset', 'view_mode'];

    $this->blazyManager->getModuleHandler()->alter('blazy_form_element', $form, $definition);

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#type']) && !in_array($form[$key]['#type'], $excludes)) {
        if (!isset($form[$key]['#default_value']) && isset($settings[$key])) {
          $value = is_array($settings[$key]) ? array_values((array) $settings[$key]) : $settings[$key];

          if (!empty($definition['grid_required']) && $key == 'grid' && empty($settings[$key])) {
            $value = 3;
          }
          $form[$key]['#default_value'] = $value;
        }
        if (!isset($form[$key]['#attributes']) && isset($form[$key]['#description'])) {
          $form[$key]['#attributes'] = ['class' => ['is-tooltip']];
        }

        if ($admin_css) {
          if ($form[$key]['#type'] == 'checkbox' && $form[$key]['#type'] != 'checkboxes') {
            $form[$key]['#field_suffix'] = '&nbsp;';
            $form[$key]['#title_display'] = 'before';
          }
          elseif ($form[$key]['#type'] == 'checkboxes' && !empty($form[$key]['#options'])) {
            $form[$key]['#attributes']['class'][] = 'form-wrapper--checkboxes';
            $form[$key]['#attributes']['class'][] = 'form-wrapper--' . str_replace('_', '-', $key);
            $count = count($form[$key]['#options']);
            $form[$key]['#attributes']['class'][] = 'form-wrapper--count-' . ($count > 3 ? 'max' : $count);

            foreach ($form[$key]['#options'] as $i => $option) {
              $form[$key][$i]['#field_suffix'] = '&nbsp;';
              $form[$key][$i]['#title_display'] = 'before';
            }
          }
        }

        if ($form[$key]['#type'] == 'select' && !in_array($key, $selects)) {
          if (!isset($form[$key]['#empty_option']) && empty($form[$key]['#required'])) {
            $form[$key]['#empty_option'] = $this->t('- None -');
          }
          if (!empty($form[$key]['#required'])) {
            unset($form[$key]['#empty_option']);
          }
        }

        if (!isset($form[$key]['#enforced']) && !empty($definition['vanilla']) && isset($form[$key]['#type'])) {
          $states['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          if (isset($form[$key]['#states'])) {
            $form[$key]['#states']['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          }
          else {
            $form[$key]['#states'] = $states;
          }
        }
      }

      $form[$key]['#wrapper_attributes']['class'][] = 'form-item--' . str_replace('_', '-', $key);

      if (isset($form[$key]['#access']) && $form[$key]['#access'] == FALSE) {
        unset($form[$key]['#default_value']);
      }

      if (in_array($key, BlazyDefault::deprecatedSettings())) {
        unset($form[$key]['#default_value']);
      }
    }

    if ($admin_css) {
      $form['closing']['#attached']['library'][] = 'blazy/admin';
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_complete_form_element', $form, $definition);
  }

  /**
   * Returns time in interval for select options.
   */
  public function getCacheOptions() {
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];

    $period = array_map([$this->dateFormatter, 'formatInterval'],
      array_combine($period, $period));
    $period[0] = '<' . $this->t('No caching') . '>';
    return $period + [Cache::PERMANENT => $this->t('Permanent')];
  }

  /**
   * Returns available entities for select options.
   */
  public function getEntityAsOptions($entity_type = '') {
    $options = [];
    if ($entities = $this->blazyManager->entityLoadMultiple($entity_type)) {
      foreach ($entities as $entity) {
        $options[$entity->id()] = Html::escape($entity->label());
      }
      ksort($options);
    }
    return $options;
  }

  /**
   * Returns available optionsets for select options.
   */
  public function getOptionsetOptions($entity_type = '') {
    return $this->getEntityAsOptions($entity_type);
  }

  /**
   * Returns available view modes for select options.
   */
  public function getViewModeOptions($target_type) {
    return $this->entityDisplayRepository->getViewModeOptions($target_type);
  }

  /**
   * Returns Responsive image for select options.
   */
  public function getResponsiveImageOptions() {
    $options = [];
    if ($this->blazyManager()->getModuleHandler()->moduleExists('responsive_image')) {
      $image_styles = $this->blazyManager()->entityLoadMultiple('responsive_image_style');
      if (!empty($image_styles)) {
        foreach ($image_styles as $name => $image_style) {
          if ($image_style->hasImageStyleMappings()) {
            $options[$name] = Html::escape($image_style->label());
          }
        }
      }
    }
    return $options;
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * Thanks to SAM152 at colorbox.module for the little sweet idea.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   * @param array $definition
   *   The foem definitions or settings.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state, array $definition = []) {
    $lightboxes = [];

    foreach ($this->blazyManager->getLightboxes() as $key => $lightbox) {
      $lightboxes[$key]['value'] = $lightbox;
    }

    $states = [
      static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED => [
        'visible' => [
          'select[name$="[responsive_image_style]"]' => ['value' => ''],
        ],
      ],
      static::STATE_LIGHTBOX_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_LIGHTBOX_CUSTOM => [
        'visible' => [
          'select[name$="[box_caption]"]' => ['value' => 'custom'],
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_IFRAME_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => ['value' => 'media'],
        ],
      ],
      static::STATE_THUMBNAIL_STYLE_ENABLED => [
        'visible' => [
          'select[name$="[thumbnail_style]"]' => ['!value' => ''],
        ],
      ],
      static::STATE_IMAGE_RENDERED_ENABLED => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['!value' => 'rendered'],
        ],
      ],
    ];
    return $states[$state];
  }

  /**
   * Deprecated method to remove.
   *
   * @todo remove once sub-modules remove this method.
   * @see https://www.drupal.org/node/3105243
   */
  public function breakpointsForm(array &$form, $definition = []) {}

}

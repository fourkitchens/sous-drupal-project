<?php

namespace Drupal\entity_browser\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;

/**
 * Defines a bulk operation form element that works with entity browser.
 *
 * @ViewsField("entity_browser_select")
 */
class SelectForm extends FieldPluginBase {

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The entity browser selection storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $selectionStorage;

  /**
   * EntityBrowser constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $selection_storage
   *   The selection storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, KeyValueStoreExpirableInterface $selection_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->selectionStorage = $selection_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('entity_browser.selection_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['use_field_cardinality'] = [
      'default' => FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['use_field_cardinality'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use field cardinality'),
      '#default_value' => $this->options['use_field_cardinality'],
      '#description' => $this->t('If the view is used in a context where cardinality is 1, use radios instead of checkboxes.'),
    ];
  }

  /**
   * Returns the ID for a result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return string
   *   The row ID, in the form ENTITY_TYPE:ENTITY_ID.
   */
  public function getRowId(ResultRow $row) {
    $entity = $this->getEntity($row);
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create('<!--form-item-' . $this->options['id'] . '--' . $this->getRowId($values) . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $render
   *   An associative array containing the structure of the form.
   */
  public function viewsForm(&$render) {
    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $render[$this->options['id']]['#tree'] = TRUE;
      $render[$this->options['id']]['#printed'] = TRUE;

      $cardinality = $this->getCardinality();
      $use_field_cardinality = $this->options['use_field_cardinality'];

      $use_radios = ($use_field_cardinality && $cardinality === 1);

      foreach ($this->view->result as $row) {
        $value = $this->getRowId($row);

        $render[$this->options['id']][$value] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Select this item'),
          '#title_display' => 'invisible',
          '#return_value' => $value,
          '#attributes' => ['name' => "entity_browser_select[$value]"],
          '#default_value' => NULL,
        ];

        if ($use_radios) {
          $render[$this->options['id']][$value]['#type'] = 'radio';
          $render[$this->options['id']][$value]['#attributes'] = ['name' => "entity_browser_select"];
          $render[$this->options['id']][$value]['#parents'] = ['entity_browser_select'];
          // Add the #value property to suppress a php notice in Radio.php.
          $render[$this->options['id']][$value]['#value'] = FALSE;
        }
      }

      $render['entity_browser_select_form_metadata'] = [
        'cardinality' => [
          '#type' => 'hidden',
          '#value' => $cardinality,
        ],
        'use_field_cardinality' => [
          '#type' => 'hidden',
          '#value' => (int) $use_field_cardinality,
        ],
        '#tree' => TRUE,
      ];
    }

    $render['view']['#cache']['tags'][] = 'config:entity_browser.settings';
  }

  /**
   * Get widget context from entity_browser.selection_storage service.
   *
   * @return array
   *   Array of contextual information.
   */
  protected function getWidgetContext() {
    if ($this->currentRequest->query->has('uuid')) {
      $uuid = $this->currentRequest->query->get('uuid');
      if ($storage = $this->selectionStorage->get($uuid)) {
        if (isset($storage['widget_context'])) {
          return $storage['widget_context'];
        }
      }
    }
    return [];
  }

  /**
   * Get cardinality from widget context.
   *
   * @return int|null
   *   Returns cardinality from widget context.
   */
  protected function getCardinality() {
    $widget_context = $this->getWidgetContext();
    if (!empty($widget_context['cardinality'])) {
      return $widget_context['cardinality'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}

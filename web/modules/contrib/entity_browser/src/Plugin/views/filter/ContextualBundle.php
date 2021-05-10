<?php

namespace Drupal\entity_browser\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\filter\Bundle;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_browser_bundle")
 */
class ContextualBundle extends Bundle {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A request stack symfony instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity browser selection storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $selectionStorage;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, KeyValueStoreExpirableInterface $selection_storage, EntityTypeBundleInfoInterface $bundle_info_service) {
    HandlerBase::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->selectionStorage = $selection_storage;
    $this->is_handler = TRUE;
    $this->bundleInfoService = $bundle_info_service;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeId = $this->getEntityType();
    $this->entityType = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $this->real_field = $this->entityType->getKey('bundle');

    // Pull $this->value from entity browser storage.
    $current_request = $this->requestStack->getCurrentRequest();
    if ($current_request->query->has('uuid')) {
      $uuid = $current_request->query->get('uuid');
      if ($storage = $this->selectionStorage->get($uuid)) {
        if (isset($storage['widget_context']) && !empty($storage['widget_context']['target_bundles'])) {
          $this->value = $storage['widget_context']['target_bundles'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('entity_browser.selection_storage'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    // Don't allow selecting "not in".
    $form['operator'] = [
      '#type' => 'hidden',
      '#value' => 'in',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($this->value), 'in');
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Removes the bundle dependencies in parent::calculateDependencies, since
    // they are dynamic.
    $dependencies = HandlerBase::calculateDependencies();
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = parent::getValueOptions();

    // Don't limit on config form, we'll display all greyed out.
    if ($this->requestStack->getCurrentRequest()->attributes->get('_route') == 'views_ui.form_handler') {
      return $this->valueOptions;
    }

    // Remove options that are not in this context.
    foreach ($this->valueOptions as $key => $value) {
      if (!in_array($key, $this->value)) {
        unset($this->valueOptions[$key]);
      }
    }

    return $this->valueOptions;
  }

  /**
   * Determines if the input from a filter should change the generated query.
   *
   * @param array $input
   *   The exposed data for this view.
   *
   * @return bool
   *   TRUE if the input for this filter should be included in the view query.
   *   FALSE otherwise.
   */
  public function acceptExposedInput($input) {

    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // If "All" option selected, Do call ::query.
    $identifier = $this->options['expose']['identifier'];
    if (!empty($input[$identifier]) && $input[$identifier] == 'All') {
      return TRUE;
    }

    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // Disable element on config form.
    if ($this->requestStack->getCurrentRequest()->attributes->get('_route') == 'views_ui.form_handler') {
      $form['value']['#default_value'] = array_combine(array_keys($form['value']['#options']), array_keys($form['value']['#options']));
      $form['value']['#disabled'] = TRUE;
      $form['value']['#description'] = $this->t('You cannot edit this list because the options update in response to entity browser context.');
    }
    // Hide element in exposed filter form if there's only one value.
    elseif (!empty($this->value) && count($this->value) === 1) {
      $form['value'] = [
        '#type' => 'hidden',
        '#value' => array_values($this->value)[0],
      ];
    }

  }

}

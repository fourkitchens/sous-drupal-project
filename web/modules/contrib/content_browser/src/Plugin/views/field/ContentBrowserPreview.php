<?php

namespace Drupal\content_browser\Plugin\views\field;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a custom field that renders a preview of a Content Entity type,
 * and allows for changing field settings with exposed input.
 *
 * @ViewsField("content_browser_preview")
 */
class ContentBrowserPreview extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

    /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $build = [];

    $entity = $this->getEntity($values);

    // If the related Entity is not a Content Entity, return early.
    if (!$entity instanceof ContentEntityInterface) {
      return $build;
    }

    $exposed_view_mode = isset($this->view->exposed_data['view_mode']) ? $this->view->exposed_data['view_mode'] : FALSE;
    $view_mode_options = $this->entityDisplayRepository->getViewModeOptions($this->getEntityType());

    if ($this->options['exposed_view_mode'] && $exposed_view_mode && isset($view_mode_options[$exposed_view_mode])) {
      $view_mode = $exposed_view_mode;
    }
    else {
      $view_mode = isset($this->options['view_mode']) ? $this->options['view_mode'] : 'teaser';
    }

    $entity_view = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);

    return $entity_view;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];

    $options['view_mode'] = ['default' => 'teaser'];
    $options['exposed_view_mode'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['view_mode'] = [
      '#title' => $this->t('Content view mode'),
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getEntityType()),
      '#type' => 'select',
      '#default_value' => $this->options['view_mode'],
      '#description' => $this->t('The view mode which you would like the content to be previewed in.'),
      '#weight' => -105,
    ];

    $form['exposed_view_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exposed view mode'),
      '#default_value' => $this->options['exposed_view_mode'],
      '#description' => $this->t('If checked, the option to switch view modes will be exposed to the user.'),
      '#weight' => -104,
    ];
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

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

}

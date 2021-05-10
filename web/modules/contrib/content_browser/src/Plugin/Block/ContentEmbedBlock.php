<?php

namespace Drupal\content_browser\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides the "Content Embed" block.
 *
 * @Block(
 *   id = "content_embed",
 *   admin_label = @Translation("Content Embed"),
 *   category = @Translation("Embed")
 * )
 */
class ContentEmbedBlock extends BlockBase {

  /**
   * The number of times this block allows rendering the same entity.
   *
   * @var int
   */
  const RECURSIVE_RENDER_LIMIT = 2;

  /**
   * An array of counters for the recursive rendering protection.
   *
   * @var array
   */
  protected static $recursiveRenderDepth = [];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => '',
      'nids' => [],
      'uuids' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $entities = $form_state->getValue([
      'settings',
      'selection',
      'nids',
      'entities',
    ], []);
    $nids = [];
    foreach ($entities as $entity) {
      $nids[] = $entity->id();
    }
    if (empty($nids)) {
      $nids = $this->getDefaultNIDs();
    }

    $form['selection'] = $this->browserForm($nids);

    /** @var \Drupal\Core\Entity\EntityDisplayRepository $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $view_mode_options = $entity_display_repository->getViewModeOptions('node');

    $form['view_mode'] = [
      '#type' => 'select',
      '#options' => $view_mode_options,
      '#title' => t('View mode'),
      '#default_value' => $this->configuration['view_mode'],
    ];

    return $form;
  }

  /**
   * Constructs parts of the form needed to use Entity Browser.
   *
   * @param array $nids
   *   An array of Node IDs.
   *
   * @return array
   *   A render array representing Entity Browser components.
   */
  public function browserForm($nids) {
    $selection = [
      '#type' => 'container',
      '#attributes' => ['id' => 'content-embed-block-browser'],
    ];

    $selection['nids'] = [
      '#type' => 'entity_browser',
      '#entity_browser' => 'browse_content',
      '#entity_browser_validators' => [
        'entity_type' => ['type' => 'node'],
      ],
      '#process' => [
        [
          '\Drupal\entity_browser\Element\EntityBrowserElement',
          'processEntityBrowser',
        ],
        [get_called_class(), 'processEntityBrowser'],
      ],
    ];

    $order_class = 'content-embed-block-delta-order';

    $selection['table'] = [
      '#type' => 'table',
      '#header' => [
        t('Title'),
        t('Type'),
        t('Order', [], ['context' => 'Sort order']),
      ],
      '#empty' => t('No content yet'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ],
      ],
    ];

    $delta = 0;
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');

    foreach ($nids as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = Node::load($nid);

      $selection['table'][$nid] = [
        '#attributes' => [
          'class' => ['draggable'],
          'data-entity-id' => $node->getEntityTypeId() . ':' . $nid,
        ],
        'title' => ['#markup' => $node->label()],
        'type' => ['#markup' => $bundle_info[$node->bundle()]['label']],
        '_weight' => [
          '#type' => 'weight',
          '#title' => t('Weight for row @number', ['@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#delta' => count($nids),
          '#default_value' => $delta,
          '#attributes' => ['class' => [$order_class]],
        ],
      ];

      $delta++;
    }

    return $selection;
  }

  /**
   * AJAX callback: Re-renders the Entity Browser button/table.
   */
  public static function updateCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -2);
    $selection = NestedArray::getValue($form, $parents);
    return $selection;
  }

  /**
   * Render API callback: Processes the entity browser element.
   */
  public static function processEntityBrowser(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['entity_ids']['#ajax'] = [
      'callback' => [get_called_class(), 'updateCallback'],
      'wrapper' => 'content-embed-block-browser',
      'event' => 'entity_browser_value_updated',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['nids'] = [];
    $this->configuration['uuids'] = [];
    foreach ($form_state->getValue(['selection', 'table'], []) as $nid => $settings) {
      $this->configuration['nids'][] = $nid;
    }
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');

    foreach ($this->getDefaultNIDs() as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = Node::load($nid);
      if ($node && $node->access('view')) {
        if (isset(static::$recursiveRenderDepth[$nid])) {
          static::$recursiveRenderDepth[$nid]++;
        }
        else {
          static::$recursiveRenderDepth[$nid] = 1;
        }

        // Protect ourselves from recursive rendering.
        if (static::$recursiveRenderDepth[$nid] > static::RECURSIVE_RENDER_LIMIT) {
          return $build;
        }

        $build[] = $view_builder->view($node, $this->configuration['view_mode']);
      }
    }

    return $build;
  }

  /**
   * Gets the default NIDs for this Block.
   *
   * @return array
   *   An array of Node IDs that are currently set in the Block configuration.
   */
  protected function getDefaultNIDs() {
    // We optionally support UUIDs being put directly to our configuration, to
    // support profiles providing Content Embed Blocks with default config.
    if (!empty($this->configuration['uuids'])) {
      $nids = \Drupal::entityQuery('node')
        ->condition('uuid', $this->configuration['uuids'], 'IN')
        ->execute();
    }
    else {
      $nids = [];
    }

    // Merge in the normal configuration.
    $nids += $this->configuration['nids'];

    return $nids;
  }

}

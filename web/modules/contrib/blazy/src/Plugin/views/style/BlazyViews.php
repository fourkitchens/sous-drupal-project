<?php

namespace Drupal\blazy\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Dejavu\BlazyStyleBaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Blazy style plugin.
 */
class BlazyViews extends StylePluginBase {

  use BlazyStyleBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'));
  }

  /**
   * Returns the blazy admin.
   */
  public function admin() {
    return \Drupal::service('blazy.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (BlazyDefault::gridSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides StylePluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definition = [
      'namespace'     => 'blazy',
      'grid_form'     => TRUE,
      'grid_required' => TRUE,
      'settings'      => $this->options,
      'style'         => TRUE,
      'opening_class' => 'form--views',
      '_views'        => TRUE,
    ];

    // Build the form.
    $this->admin()->openingForm($form, $definition);
    $this->admin()->gridForm($form, $definition);
    $this->admin()->finalizeForm($form, $definition);

    // Blazy doesn't need complex grid with multiple groups.
    unset($form['layout'], $form['preserve_keys'], $form['visible_items']);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings              = $this->buildSettings();
    $settings['item_id']   = 'content';
    $settings['namespace'] = 'blazy';

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $items = [];
      foreach ($rows as $index => $row) {
        $this->view->row_index = $index;

        $items[$index] = $this->view->rowPlugin->render($row);
      }

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];
      $build = ['items' => $items, 'settings' => $settings];
      $elements = $this->blazyManager->build($build);

      unset($this->view->row_index, $items);
    }

    return $elements;
  }

}

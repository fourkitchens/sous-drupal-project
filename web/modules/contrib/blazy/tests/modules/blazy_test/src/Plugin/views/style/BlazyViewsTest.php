<?php

namespace Drupal\blazy_test\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Dejavu\BlazyStylePluginBase;

/**
 * Blazy Views Test style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "blazy_test",
 *   title = @Translation("Blazy Views Test"),
 *   help = @Translation("Display the results in a Blazy Views Test."),
 *   theme = "blazy_test",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class BlazyViewsTest extends BlazyStylePluginBase {

  /**
   * Returns the blazy admin.
   */
  public function admin() {
    return \Drupal::service('blazy_test.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    $defaults = BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
    foreach ($defaults as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = [
      'captions',
      'layouts',
      'images',
      'links',
      'titles',
      'classes',
      'overlays',
      'thumbnails',
      'layouts',
    ];

    $definition = $this->getDefinedFieldOptions($fields);

    $definition += [
      'namespace' => 'blazy',
      'settings'  => $this->options,
      'style'     => TRUE,
    ];

    // Build the form.
    $this->admin()->buildSettingsForm($form, $definition);

    // Blazy doesn't need complex grid with multiple groups.
    unset($form['layout'], $form['preserve_keys'], $form['grid_header'], $form['visible_items']);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->buildSettings() + BlazyDefault::entitySettings();

    $settings['item_id']   = 'box';
    $settings['caption']   = array_filter($settings['caption']);
    $settings['namespace'] = 'blazy';
    $settings['ratio']     = '';

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $items = $this->buildElements($settings, $rows);

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];

      $build = ['items' => $items, 'settings' => $settings];
      $elements = $this->blazyManager->build($build);
    }

    return $elements;
  }

  /**
   * Returns blazy_test contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $item_id = $settings['item_id'];

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $box              = [];
      $box[$item_id]    = [];
      $box['#settings'] = $settings;

      // Use Vanilla if so configured.
      if (!empty($settings['vanilla'])) {
        $box[$item_id] = $view->rowPlugin->render($row);
      }
      else {
        // Build individual row/ element contents.
        $this->buildElement($box, $row, $index);
      }

      // Build blazy items.
      $build[] = $box;
      unset($box);
    }

    unset($view->row_index);
    return $build;
  }

}

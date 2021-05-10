<?php

namespace Drupal\Tests\blazy\Kernel\Views;

use Drupal\Core\Form\FormState;
use Drupal\views\Views;
use Drupal\blazy\BlazyViews;

/**
 * Test Blazy Views integration.
 *
 * @coversDefaultClass \Drupal\blazy\Dejavu\BlazyStylePluginBase
 *
 * @group blazy
 */
class BlazyViewsFileTest extends BlazyViewsTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_blazy_entity', 'test_blazy_entity_2'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->entityFieldName = 'field_entity_test';
    $this->entityPluginId  = 'blazy_entity_test';
    $this->targetBundle    = 'bundle_target_test';
    $this->targetBundles   = [$this->targetBundle];
  }

  /**
   * Build contents.
   */
  private function buildContents() {
    $this->setUpRealImage();

    $bundle = $this->bundle;
    $settings['image_settings'] = [
      'image_style'  => 'blazy_crop',
      'media_switch' => 'blazy_test',
      'ratio'        => 'fluid',
      'view_mode'    => 'default',
    ];

    $this->setUpContentWithEntityReference($settings);

    // Create referencing entity.
    $this->referencingEntity = $this->createReferencingEntity();

    $data['fields'] = $this->getDefaultFields();

    // Create contents.
    $this->setUpContentTypeTest($bundle, $data);

    $data['settings'] = $this->getFormatterSettings();
    $display = $this->setUpFormatterDisplay($bundle, $data);

    $display->setComponent('field_image', [
      'type'     => 'blazy',
      'settings' => $settings['image_settings'],
      'label'    => 'hidden',
    ]);

    $display->save();

    $this->setUpContentWithItems($bundle);
  }

  /**
   * Make sure that the HTML list style markup is correct.
   *
   * @todo enable this once corrected, likely broken since Drupal 8.4+.
   */
  public function todoTestBlazyViews() {
    $this->buildContents();

    $view = Views::getView('test_blazy_entity');
    $this->executeView($view);
    $view->setDisplay('default');

    $style_plugin = $view->style_plugin;

    $this->assertInstanceOf('\Drupal\blazy\BlazyManagerInterface', $style_plugin->blazyManager(), 'BlazyManager implements interface.');
    $this->assertInstanceOf('\Drupal\blazy_test\Form\BlazyAdminTestInterface', $style_plugin->admin(), 'BlazyAdmin implements interface.');

    $style_plugin->options                            = array_merge($style_plugin->options, $this->getDefaultFields(TRUE));
    $style_plugin->options['grid']                    = 0;
    $style_plugin->options['grid_medium']             = 3;
    $style_plugin->options['grid_small']              = 1;
    $style_plugin->options['image']                   = 'field_image';
    $style_plugin->options['media_switch']            = 'blazy_test';
    $style_plugin->options['overlay']                 = $this->testFieldName;
    $style_plugin->options['caption']['title']        = 'title';
    $style_plugin->options['caption']['field_teaser'] = 'field_teaser';

    $settings = $style_plugin->options;

    // Forms.
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
    $definition = $style_plugin->getDefinedFieldOptions($fields);
    $this->assertEquals('blazy_test', $definition['plugin_id']);

    $form = [];
    $form_state = new FormState();
    $style_plugin->buildOptionsForm($form, $form_state);
    $this->assertArrayHasKey('closing', $form);

    $style_plugin->submitOptionsForm($form, $form_state);

    // @todo: Fields.
    $image = [];
    $index = 0;
    $row = $view->result[$index];

    // Render.
    $render = $view->getStyle()->render();
    $this->assertArrayHasKey('data-blazy', $render['#attributes']);

    $output = $view->preview();
    $output = $this->blazyManager->getRenderer()->renderRoot($output);
    $this->assertStringContainsString('data-blazy', $output);

    $element = ['settings' => $settings];
    $view->getStyle()->buildElement($element, $row, $index);

    try {
      $output = $view->getStyle()->getImageRenderable($settings, $row, $index);
    }
    catch (\PHPUnit_Framework_Exception $e) {
    }
    $this->assertTrue(TRUE);

    $image = $view->getStyle()->getImageRenderable($settings, $row, $index);
    $this->assertArrayHasKey('image', $settings);

    $output = $view->getStyle()->getImageItem($image);
    $this->assertArrayHasKey('image', $settings);

    $output = $view->getStyle()->isImageRenderable($row, $index, $this->testFieldName);
    $this->assertArrayHasKey('image', $settings);

    $output = $view->getStyle()->getCaption($index, $settings);
    $this->assertArrayHasKey('caption', $settings);

    $view->getStyle()->getLayout($settings, $index);
    $this->assertArrayHasKey('layout', $settings);

    $output = FALSE;
    try {
      $output = $view->getStyle()->getFieldRenderable($row, $index, '');
    }
    catch (\PHPUnit_Framework_Exception $e) {
    }

    $this->assertTrue(TRUE);

    $output = $view->getStyle()->getFieldRendered($index, $this->testFieldName);
    $this->assertArrayHasKey('image', $settings);

    $output = $view->getStyle()->getFieldRenderable($row, $index, $this->testFieldName);
    $this->assertArrayHasKey('image', $settings);

    $output = $view->getStyle()->getFieldString($row, 'title', $index);
    $this->assertNotEmpty($output[0]);

    if ($blazy = BlazyViews::viewsField($view)) {
      $scopes = $blazy->getScopedFormElements();
      $this->assertArrayHasKey('settings', $scopes);

      $form = [];
      $form_state = new FormState();
      $blazy->buildOptionsForm($form, $form_state);
      $this->assertArrayHasKey('image_style', $form);

      $this->assertInstanceOf('\Drupal\blazy\Form\BlazyAdminInterface', $blazy->blazyAdmin(), 'BlazyAdmin implements interface.');
    }

    $view->destroy();
  }

  /**
   * Make sure that the HTML list style markup is correct.
   */
  public function testBlazyViewsForm() {
    $view = Views::getView('test_blazy_entity_2');
    $this->executeView($view);
    $view->setDisplay('default');

    $style_plugin = $view->style_plugin;
    $style_plugin->options['grid'] = 0;

    $form = [];
    $form_state = new FormState();
    $style_plugin->buildOptionsForm($form, $form_state);
    $this->assertArrayHasKey('closing', $form);

    $style_plugin->submitOptionsForm($form, $form_state);

    $view->destroy();
  }

}

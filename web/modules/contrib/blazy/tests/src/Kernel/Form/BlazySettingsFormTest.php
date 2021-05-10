<?php

namespace Drupal\Tests\blazy\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\blazy_ui\Form\BlazySettingsForm;

/**
 * Tests the Blazy UI settings form.
 *
 * @coversDefaultClass \Drupal\blazy_ui\Form\BlazySettingsForm
 *
 * @group blazy
 */
class BlazySettingsFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The Blazy form object under test.
   *
   * @var \Drupal\blazy_ui\Form\BlazySettingsForm
   */
  protected $blazySettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'file',
    'image',
    'media',
    'blazy',
    'blazy_ui',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);

    $this->blazyManager = $this->container->get('blazy.manager');

    $this->blazySettingsForm = BlazySettingsForm::create($this->container);
  }

  /**
   * Tests for \Drupal\blazy_ui\Form\BlazySettingsForm.
   *
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::submitForm
   */
  public function testBlazySettingsForm() {
    // Emulate a form state of a submitted form.
    $form_state = (new FormState())->setValues([
      'admin_css'        => TRUE,
      'responsive_image' => FALSE,
    ]);

    $this->assertInstanceOf(FormInterface::class, $this->blazySettingsForm);
    $this->assertTrue($this->blazyManager->getConfigFactory()->get('blazy.settings')->get('admin_css'));

    $id = $this->blazySettingsForm->getFormId();
    $this->assertEquals('blazy_settings_form', $id);

    $method = new \ReflectionMethod(BlazySettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->blazySettingsForm);
    $this->assertEquals(['blazy.settings'], $name);

    $form = $this->blazySettingsForm->buildForm([], $form_state);
    $this->blazySettingsForm->submitForm($form, $form_state);
  }

}

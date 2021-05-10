<?php

namespace Drupal\Tests\blazy\Unit\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;
use Drupal\blazy\Dejavu\BlazyAdminExtended;

/**
 * Tests the Blazy admin formatter form.
 *
 * @coversDefaultClass \Drupal\blazy\Dejavu\BlazyAdminExtended
 * @group blazy
 */
class BlazyAdminExtendedUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * The mocked translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpUnitServices();
    $this->setUpUnitContainer();

    $this->stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $this->entityDisplayRepository = $this->createMock('Drupal\Core\Entity\EntityDisplayRepositoryInterface');
    $this->typedConfig = $this->createMock('Drupal\Core\Config\TypedConfigManagerInterface');
    $this->dateFormatter = $this->getMockBuilder('Drupal\Core\Datetime\DateFormatter')
      ->disableOriginalConstructor()
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('entity_display.repository', $this->entityDisplayRepository);
    $container->set('config.typed', $this->typedConfig);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('date.formatter', $this->dateFormatter);
    $container->set('blazy.manager', $this->blazyManager);

    \Drupal::setContainer($container);

    $this->blazyAdminExtended = new BlazyAdminExtended(
      $this->entityDisplayRepository,
      $this->typedConfig,
      $this->dateFormatter,
      $this->blazyManager
    );
  }

  /**
   * Provide test cases for ::testBuildSettingsForm.
   */
  public function providerTestBuildSettingsForm() {
    return [
      [FALSE, FALSE],
      [TRUE, TRUE],
      [TRUE, FALSE],
      [FALSE, TRUE],
    ];
  }

  /**
   * Tests BlazyAdminExtended.
   *
   * @covers ::openingForm
   * @covers ::imageStyleForm
   * @covers ::fieldableForm
   * @covers ::mediaSwitchForm
   * @covers ::gridForm
   * @covers ::closingForm
   * @covers ::finalizeForm
   * @dataProvider providerTestBuildSettingsForm
   */
  public function testBuildSettingsForm($id, $vanilla) {
    $form = [];
    $definition = $this->getDefaulEntityFormatterDefinition() + $this->getDefaultFormatterDefinition();

    $definition['settings'] += $this->getDefaultFields(TRUE);
    $definition['id'] = $id;
    $definition['vanilla'] = $vanilla;
    $definition['_views'] = TRUE;

    $this->blazyAdminExtended->openingForm($form, $definition);
    $this->assertEquals($vanilla, !empty($form['vanilla']));

    $this->blazyAdminExtended->fieldableForm($form, $definition);
    $this->assertEquals($id, !empty($form['id']));

    $this->blazyAdminExtended->closingForm($form, $definition);
    $this->assertArrayHasKey('closing', $form);
  }

}

namespace Drupal\blazy\Form;

if (!function_exists('responsive_image_get_image_dimensions')) {

  /**
   * Dummy function.
   */
  function responsive_image_get_image_dimensions() {
    // Empty block to satisfy coder.
  }

}

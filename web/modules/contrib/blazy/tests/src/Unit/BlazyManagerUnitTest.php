<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;

/**
 * @coversDefaultClass \Drupal\blazy\BlazyManager
 *
 * @group blazy
 */
class BlazyManagerUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpUnitServices();
    $this->setUpUnitContainer();
    $this->setUpUnitImages();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::getEntityTypeManager
   * @covers ::getModuleHandler
   * @covers ::getRenderer
   * @covers ::getCache
   * @covers ::getConfigFactory
   */
  public function testBlazyManagerServiceInstances() {
    $this->assertInstanceOf('\Drupal\Core\Entity\EntityTypeManagerInterface', $this->blazyManager->getEntityTypeManager());
    $this->assertInstanceOf('\Drupal\Core\Extension\ModuleHandlerInterface', $this->blazyManager->getModuleHandler());
    $this->assertInstanceOf('\Drupal\Core\Render\RendererInterface', $this->blazyManager->getRenderer());
    $this->assertInstanceOf('\Drupal\Core\Config\ConfigFactoryInterface', $this->blazyManager->getConfigFactory());
    $this->assertInstanceOf('\Drupal\Core\Cache\CacheBackendInterface', $this->blazyManager->getCache());
  }

  /**
   * Tests cases for config.
   *
   * @covers ::configLoad
   */
  public function testConfigLoad() {
    $this->blazyManager->expects($this->any())
      ->method('configLoad')
      ->with('blazy')
      ->willReturn(['loadInvisible' => FALSE]);

    $blazy = $this->blazyManager->configLoad('blazy');
    $this->assertArrayHasKey('loadInvisible', $blazy);

    $this->blazyManager->expects($this->any())
      ->method('configLoad')
      ->with('admin_css')
      ->willReturn(TRUE);

    $this->blazyManager->expects($this->any())
      ->method('configLoad')
      ->with('responsive_image')
      ->willReturn(TRUE);
  }

  /**
   * Tests cases for config.
   *
   * @covers ::entityLoad
   * @covers ::entityLoadMultiple
   */
  public function testEntityLoadImageStyle() {
    $styles = $this->setUpImageStyle();
    $ids = array_keys($styles);

    $this->blazyManager->expects($this->any())
      ->method('entityLoadMultiple')
      ->with('image_style')
      ->willReturn($styles);

    $multiple = $this->blazyManager->entityLoadMultiple('image_style', $ids);
    $this->assertArrayHasKey('large', $multiple);

    $this->blazyManager->expects($this->any())
      ->method('entityLoad')
      ->with('large')
      ->willReturn($multiple['large']);

    $expected = $this->blazyManager->entityLoad('large', 'image_style');
    $this->assertEquals($expected, $multiple['large']);
  }

  /**
   * Tests for \Drupal\blazy\BlazyManager::getBlazy().
   *
   * @covers ::getBlazy
   * @dataProvider providerTestGetBlazy
   */
  public function testGetBlazy($uri, $content, $expected_image, $expected_render) {
    $build = [];
    $build['item'] = NULL;
    $build['content'] = $content;
    $build['settings']['uri'] = $uri;

    $theme = ['#theme' => 'blazy', '#build' => []];
    $this->blazyManager->expects($this->any())
      ->method('getBlazy')
      ->willReturn($expected_image ? $theme : []);

    $image = $this->blazyManager->getBlazy($build);
    $check_image = !$expected_image ? empty($image) : !empty($image);
    $this->assertTrue($check_image);
  }

  /**
   * Provide test cases for ::testPreRenderImage().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestGetBlazy() {
    $data[] = [
      '',
      '',
      FALSE,
      FALSE,
    ];
    $data[] = [
      'core/misc/druplicon.png',
      '',
      TRUE,
      TRUE,
    ];
    $data[] = [
      'core/misc/druplicon.png',
      '<iframe src="//www.youtube.com/watch?v=E03HFA923kw" class="b-lazy"></iframe>',
      FALSE,
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests cases for attachments.
   *
   * @covers ::attach
   * @depends testConfigLoad
   */
  public function testAttach() {
    $attach = [
      'blazy'        => TRUE,
      'grid'         => 0,
      'media'        => TRUE,
      'media_switch' => 'media',
      'ratio'        => 'fluid',
      'style'        => 'column',
    ];

    $this->blazyManager->expects($this->any())
      ->method('attach')
      ->with($attach)
      ->willReturn(['drupalSettings' => ['blazy' => []]]);

    $attachments = $this->blazyManager->attach($attach);

    $this->blazyManager->expects($this->any())
      ->method('attach')
      ->with($attach)
      ->willReturn(['drupalSettings' => ['blazy' => []]]);
    $this->assertArrayHasKey('blazy', $attachments['drupalSettings']);
  }

  /**
   * Tests cases for lightboxes.
   *
   * @covers ::getLightboxes
   */
  public function testGetLightboxes() {
    $this->blazyManager->expects($this->any())
      ->method('getLightboxes')
      ->willReturn([]);

    $lightboxes = $this->blazyManager->getLightboxes();

    $this->assertNotContains('nixbox', $lightboxes);
  }

}

namespace Drupal\blazy;

if (!function_exists('blazy_test_theme')) {

  /**
   * Dummy function.
   */
  function blazy_test_theme() {
    // Empty block to satisfy coder.
  }

}

if (!function_exists('colorbox_theme')) {

  /**
   * Dummy function.
   */
  function colorbox_theme() {
    // Empty block to satisfy coder.
  }

}

if (!function_exists('photobox_theme')) {

  /**
   * Dummy function.
   */
  function photobox_theme() {
    // Empty block to satisfy coder.
  }

}

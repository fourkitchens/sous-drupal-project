<?php

namespace Drupal\Tests\blazy\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * A Trait common for Blazy related service managers.
 */
trait BlazyManagerUnitTestTrait {

  /**
   * Setup the unit manager.
   */
  protected function setUpUnitServices() {
    $this->entityStorage      = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entityViewBuilder  = $this->createMock('Drupal\Core\Entity\EntityViewBuilderInterface');
    $this->entityTypeMock     = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityFieldManager = $this->createMock('\Drupal\Core\Entity\EntityFieldManagerInterface');
    $this->entityRepository   = $this->createMock('\Drupal\Core\Entity\EntityRepositoryInterface');
    $this->entityTypeManager  = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->renderer           = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->cache              = $this->createMock('\Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler      = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandler')->disableOriginalConstructor()->getMock();

    $this->token = $this->getMockBuilder('\Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $this->token->expects($this->any())
      ->method('replace')
      ->willReturnArgument(0);

    $this->configFactory = $this->getConfigFactoryStub([
      'blazy.settings' => [
        'admin_css' => TRUE,
        'responsive_image' => TRUE,
        'one_pixel' => TRUE,
        'blazy' => ['loadInvisible' => FALSE, 'offset' => 100],
      ],
    ]);

    $this->blazyManager = $this->getMockBuilder('\Drupal\blazy\BlazyManager')
      ->disableOriginalConstructor()
      ->getMock();

    $this->blazyManager->expects($this->any())
      ->method('getModuleHandler')
      ->willReturn($this->moduleHandler);

    $this->blazyManager->expects($this->any())
      ->method('getEntityTypeManager')
      ->willReturn($this->entityTypeManager);

    $this->blazyManager->expects($this->any())
      ->method('getRenderer')
      ->willReturn($this->renderer);

    $this->blazyManager->expects($this->any())
      ->method('getConfigFactory')
      ->willReturn($this->configFactory);

    $this->blazyManager->expects($this->any())
      ->method('getCache')
      ->willReturn($this->cache);
  }

  /**
   * Setup the unit manager.
   */
  protected function setUpUnitContainer() {
    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('entity.repository', $this->entityRepository);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('module_handler', $this->moduleHandler);
    $container->set('renderer', $this->renderer);
    $container->set('config.factory', $this->configFactory);
    $container->set('cache.default', $this->cache);
    $container->set('token', $this->token);
    $container->set('blazy.manager', $this->blazyManager);

    \Drupal::setContainer($container);
  }

  /**
   * Prepare image styles.
   */
  protected function setUpImageStyle() {
    $styles = [];

    $dummies = ['blazy_crop', 'large', 'medium', 'small'];
    foreach ($dummies as $style) {
      $mock = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $styles[$style] = $mock;
    }

    $ids = array_keys($styles);
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with($ids)
      ->willReturn($styles);

    $style = 'large';
    $storage->expects($this->any())
      ->method('load')
      ->with($style)
      ->will($this->returnValue($styles[$style]));

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('image_style')
      ->willReturn($storage);

    return $styles;
  }

  /**
   * Prepare Responsive image styles.
   */
  protected function setUpResponsiveImageStyle() {
    $styles = $image_styles = [];
    foreach (['fallback', 'small', 'medium', 'large'] as $style) {
      $mock = $this->createMock('Drupal\Core\Config\Entity\ConfigEntityInterface');
      $mock->expects($this->any())
        ->method('getConfigDependencyName')
        ->willReturn('image.style.' . $style);
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $image_styles[$style] = $mock;
    }

    foreach (['blazy_picture_test', 'blazy_responsive_test'] as $style) {
      $mock = $this->createMock('Drupal\responsive_image\ResponsiveImageStyleInterface');
      $mock->expects($this->any())
        ->method('getImageStyleIds')
        ->willReturn(array_keys($image_styles));
      $mock->expects($this->any())
        ->method('getCacheTags')
        ->willReturn([]);

      $styles[$style] = $mock;
    }

    $ids = array_keys($styles);
    $storage = $this->createMock('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->with($ids)
      ->willReturn($styles);

    $style = 'blazy_picture_test';
    $storage->expects($this->any())
      ->method('load')
      ->with($style)
      ->willReturn($styles[$style]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('responsive_image_style')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->any())
      ->method('getEntityTypeFromClass')
      ->with('Drupal\image\Entity\ImageStyle')
      ->willReturn('image_style');

    return $styles;
  }

}

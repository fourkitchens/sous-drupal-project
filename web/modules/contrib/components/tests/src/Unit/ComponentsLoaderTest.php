<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\components\Template\Loader\ComponentsLoader
 * @group components
 */
class ComponentsLoaderTest extends UnitTestCase {

  /**
   * The components info service.
   *
   * @var \Drupal\components\Template\ComponentsInfo|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $componentsInfo;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeManager;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\Loader\ComponentsLoader
   */
  protected $systemUnderTest;

  /**
   * {@inheritdoc}
   *
   * @throws \Twig\Error\LoaderError
   */
  public function setUp() {
    parent::setUp();

    // Set up components info service.
    $this->componentsInfo = $this->createMock('\Drupal\components\Template\ComponentsInfo');
    $this->componentsInfo
      ->expects($this->atLeastOnce())
      ->method('getAllThemeInfo')
      ->willReturn([
        'earth' => [
          'namespaces' => [
            'earth' => ['/earth/templates'],
          ],
        ],
        'jupiter' => [
          'namespaces' => [
            'jupiter' => ['/jupiter/templates'],
            'components' => ['/jupiter/components'],
            'jupiter_extras' => ['/jupiter/extras'],
          ],
        ],
        'luna' => [
          'namespaces' => [
            'luna' => ['/luna/templates'],
            'components' => ['/luna/components'],
            'luna_extras' => ['/luna/extras'],
            'system' => ['/luna/system'],
          ],
        ],
        'sol' => [
          'namespaces' => [
            'sol' => ['/sol/templates'],
            'components' => ['/sol/components'],
            'sol_extras' => ['/sol/extras'],
          ],
        ],
      ]);
    $this->componentsInfo
      ->expects($this->atLeastOnce())
      ->method('getAllModuleInfo')
      ->willReturn([
        'components' => [
          'namespaces' => [
            'components' => ['/components/components'],
            'components_extras' => ['/components/extras'],
            'system' => ['/components/system'],
          ],
        ],
      ]);
    $this->componentsInfo
      ->expects($this->atLeastOnce())
      ->method('isProtectedNamespace')
      ->will($this->returnValueMap([
        ['system', TRUE],
        ['components', FALSE],
        ['components_extras', FALSE],
        ['earth', FALSE],
        ['jupiter', FALSE],
        ['jupiter_extras', FALSE],
        ['luna', FALSE],
        ['luna_extras', FALSE],
        ['sol', FALSE],
        ['sol_extras', FALSE],
      ]));
    $this->componentsInfo
      ->expects($this->atLeastOnce())
      ->method('getProtectedNamespaceExtensionInfo')
      ->will($this->returnValueMap([
        [
          'system',
          ['name' => 'System', 'type' => 'module', 'package' => 'Core'],
        ],
      ]));

    // Set up theme manager data.
    $themes = [
      'sol' => $this->createMock('\Drupal\Core\Extension\Extension'),
      'earth' => $this->createMock('\Drupal\Core\Extension\Extension'),
      'luna' => $this->createMock('\Drupal\Core\Extension\Extension'),
      'jupiter' => $this->createMock('\Drupal\Core\Extension\Extension'),
    ];
    foreach (array_keys($themes) as $key) {
      $themes[$key]
        ->method('getName')
        ->willReturn($key);
    }

    $active_themes = [
      'luna' => $this->createMock('\Drupal\Core\Theme\ActiveTheme'),
      'jupiter' => $this->createMock('\Drupal\Core\Theme\ActiveTheme'),
    ];
    foreach (array_keys($active_themes) as $key) {
      $active_themes[$key]
        ->method('getName')
        ->willReturn($key);
    }
    $active_themes['luna']
      ->expects($this->atLeastOnce())
      ->method('getBaseThemeExtensions')
      ->willReturn([$themes['earth'], $themes['sol']]);
    $active_themes['jupiter']
      ->method('getBaseThemeExtensions')
      ->willReturn([$themes['sol']]);

    $this->themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $this->themeManager
      ->expects($this->atLeastOnce())
      ->method('getActiveTheme')
      ->willReturn($active_themes['luna'], $active_themes['jupiter'], $active_themes['luna']);

    $this->systemUnderTest = new ComponentsLoader($this->componentsInfo, $this->themeManager);
    $this->systemUnderTest->checkActiveTheme();
  }

  /**
   * Tests checking the active theme.
   *
   * @covers ::checkActiveTheme
   *
   * @throws \Twig\Error\LoaderError
   */
  public function testCheckActiveTheme() {
    $result = $this->systemUnderTest->checkActiveTheme();
    $this->assertEquals('jupiter', $result);
    $result = $this->systemUnderTest->checkActiveTheme();
    $this->assertEquals('luna', $result);
  }

  /**
   * Tests prepending paths to a namespace.
   *
   * @covers ::setActiveTheme
   *
   * @throws \Twig\Error\LoaderError
   */
  public function testSetActiveTheme() {
    $namespaces = [
      'components' => [
        '/luna/components',
        '/sol/components',
        '/components/components',
      ],
      'components_extras' => ['/components/extras'],
      'sol' => ['/sol/templates'],
      'sol_extras' => ['/sol/extras'],
      'earth' => ['/earth/templates'],
      'luna' => ['/luna/templates'],
      'luna_extras' => ['/luna/extras'],
    ];

    $expected = array_keys($namespaces);
    $result = $this->systemUnderTest->getNamespaces();
    $this->assertEquals($expected, $result);

    foreach ($expected as $namespace) {
      $expected = $namespaces[$namespace];
      $result = $this->systemUnderTest->getPaths($namespace);
      $this->assertEquals($expected, $result);
    }

    // The mocked theme manager will swap the active theme.
    $this->systemUnderTest->checkActiveTheme();

    $namespaces = [
      'components' => [
        '/jupiter/components',
        '/sol/components',
        '/components/components',
      ],
      'components_extras' => ['/components/extras'],
      'sol' => ['/sol/templates'],
      'sol_extras' => ['/sol/extras'],
      'jupiter' => ['/jupiter/templates'],
      'jupiter_extras' => ['/jupiter/extras'],
    ];

    $expected = array_keys($namespaces);
    $result = $this->systemUnderTest->getNamespaces();
    $this->assertEquals($expected, $result);

    foreach ($expected as $namespace) {
      $expected = $namespaces[$namespace];
      $result = $this->systemUnderTest->getPaths($namespace);
      $this->assertEquals($expected, $result);
    }
  }

  /**
   * Tests the use of the active theme cache.
   *
   * @covers ::setActiveTheme
   *
   * @throws \Twig\Error\LoaderError
   */
  public function testSetActiveThemeCache() {
    // Add a path to the sol namespace.
    $expected = ['/sol/templates', '/test/templates'];
    $this->systemUnderTest->addPath('/test/templates', 'sol');
    $result = $this->systemUnderTest->getPaths('sol');
    $this->assertEquals($expected, $result);

    // The mocked theme manager will swap the active theme twice.
    $this->systemUnderTest->checkActiveTheme();
    $this->systemUnderTest->checkActiveTheme();

    // The cache doesn't have the path added earlier.
    $expected = ['/sol/templates'];
    $result = $this->systemUnderTest->getPaths('sol');
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests adding paths to a namespace.
   *
   * @param string $path
   *   Path to prepend.
   * @param string $namespace
   *   Namespace to alter.
   * @param array $expected
   *   Expected namespace paths.
   *
   * @covers ::addPath
   *
   * @throws \Twig\Error\LoaderError
   *
   * @dataProvider providerTestAddPath
   */
  public function testAddPath(string $path, string $namespace, array $expected) {
    $this->systemUnderTest->addPath($path, $namespace);
    $result = $this->systemUnderTest->getPaths($namespace);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testAddPath().
   *
   * @see testAddPath()
   */
  public function providerTestAddPath(): array {
    return [
      'basic add' => [
        'path' => '/test/templates',
        'namespace' => 'sol',
        'expected' => ['/sol/templates', '/test/templates'],
      ],
      'Test appending on a namespace that does not exist' => [
        'path' => '/pluto/templates',
        'namespace' => 'pluto',
        'expected' => ['/pluto/templates'],
      ],
      'Test trimming the trailing slash off of the path' => [
        'path' => '/test/trim/',
        'namespace' => 'sol',
        'expected' => ['/sol/templates', '/test/trim'],
      ],
    ];
  }

  /**
   * Tests prepending paths to a namespace.
   *
   * @param string $path
   *   Path to prepend.
   * @param string $namespace
   *   Namespace to alter.
   * @param array $expected
   *   Expected namespace paths.
   *
   * @covers ::prependPath
   *
   * @throws \Twig\Error\LoaderError
   *
   * @dataProvider providerTestPrependPath
   */
  public function testPrependPath(string $path, string $namespace, array $expected) {
    $this->systemUnderTest->prependPath($path, $namespace);
    $result = $this->systemUnderTest->getPaths($namespace);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testPrependPath().
   *
   * @see testPrependPath()
   */
  public function providerTestPrependPath(): array {
    return [
      'basic prepend' => [
        'path' => '/test/templates',
        'namespace' => 'sol',
        'expected' => ['/test/templates', '/sol/templates'],
      ],
      'Test prepending on a namespace that does not exist' => [
        'path' => '/pluto/templates',
        'namespace' => 'pluto',
        'expected' => ['/pluto/templates'],
      ],
      'Test trimming the trailing slash off of the path' => [
        'path' => '/test/trim/',
        'namespace' => 'sol',
        'expected' => ['/test/trim', '/sol/templates'],
      ],
    ];
  }

}

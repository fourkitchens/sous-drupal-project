<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\ComponentsInfo;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\components\Template\ComponentsInfo
 * @group components
 */
class ComponentsInfoTest extends UnitTestCase {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $themeExtensionList;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\ComponentsInfo
   */
  protected $systemUnderTest;

  /**
   * Path to the mocked drupal directory.
   *
   * @var string
   */
  protected $rootDir;

  /**
   * Path to the mocked modules directory.
   *
   * @var string
   */
  protected $modulesDir;

  /**
   * Path to the mocked themes directory.
   *
   * @var string
   */
  protected $themesDir;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Prevent test failures due to constants used in ModuleExtensionList.
    if (!defined('DRUPAL_MINIMUM_PHP')) {
      define('DRUPAL_MINIMUM_PHP', '7.0.8');
    }

    $this->moduleExtensionList = $this->createMock('\Drupal\Core\Extension\ModuleExtensionList');
    $this->themeExtensionList = $this->createMock('\Drupal\Core\Extension\ThemeExtensionList');

    $this->rootDir = '/drupal';
    $this->modulesDir = '/drupal/modules';
    $this->themesDir = '/drupal/themes';

    // Ensure \Drupal::root() is available.
    $container = new ContainerBuilder();
    // Mock Drupal 8 Drupal::root().
    $container->set('app.root', $this->rootDir);
    // Mock Drupal 9 Drupal::root().
    $container->setParameter('app.root', $this->rootDir);
    // Mock LoggerChannelTrait.
    $loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $loggerFactory->method('get')->willReturn($this->createMock('\Drupal\Core\Logger\LoggerChannel'));
    $container->set('logger.factory', $loggerFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Creates a ComponentsInfo service after the dependencies are set up.
   */
  public function newSystemUnderTest() {
    $this->systemUnderTest = new ComponentsInfo(
      $this->moduleExtensionList,
      $this->themeExtensionList,
      $this->createMock('\Drupal\Core\Extension\ModuleHandler'),
      $this->createMock('\Drupal\Core\Theme\ThemeManager'),
      $this->createMock('\Drupal\Core\Cache\CacheBackendInterface')
    );
  }

  /**
   * Tests finding components info from extension .info.yml files.
   *
   * Since this is a protected method, we are testing it via the constructor,
   * getAllModuleInfo, and isProtectedNamespace.
   *
   * @covers ::findComponentsInfo
   */
  public function testFindComponentsInfo() {
    $this->moduleExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn([
        // Does not have a components entry.
        'system' => [
          'name' => 'System',
          'type' => 'module',
          'package' => 'Core',
          'no-components' => 'system-value',
        ],
        // Look for namespaces using 1.x API (backwards compatibility).
        'harriet_tubman' => [
          'name' => 'Harriet Tubman',
          'type' => 'module',
          'component-libraries' => [
            'harriet_tubman' => [
              'paths' => ['deprecated'],
            ],
          ],
        ],
        'phillis_wheatley' => [
          'name' => 'Phillis Wheatley',
          'type' => 'module',
          'components' => [
            'namespaces' => [
              // Namespace path is a string.
              'phillis_wheatley' => 'templates',
              // Namespace path is an array.
              'wheatley' => ['components'],
            ],
          ],
          // If components.namespaces is set, ignore 1.x API.
          'component-libraries' => [
            'wheatley' => [
              'paths' => ['deprecated'],
            ],
          ],
        ],
        // No default namespace defined.
        'edna_lewis' => [
          'name' => 'Edna Lewis',
          'type' => 'module',
          'unrelatedKey' => 'should be ignored',
          'components' => [
            'includedKey' => 'included',
            'namespaces' => [
              'lewis' => ['templates', 'components'],
            ],
          ],
        ],
        // Namespace path is relative to Drupal root.
        'tracy_chapman' => [
          'name' => 'Tracy Chapman',
          'type' => 'module',
          'components' => [
            'namespaces' => [
              'chapman' => ['templates', '/libraries/chapman/components'],
            ],
          ],
        ],
        // Manual opt-in.
        'components' => [
          'name' => 'Components!',
          'type' => 'module',
          'components' => [
            'allow_default_namespace_reuse' => TRUE,
          ],
        ],
      ]);
    $this->moduleExtensionList
      ->expects($this->exactly(6))
      ->method('getPath')
      ->willReturn(
        $this->rootDir . '/core/modules/system',
        $this->modulesDir . '/tubman',
        $this->modulesDir . '/wheatley',
        $this->modulesDir . '/lewis',
        $this->modulesDir . '/chapman',
        $this->modulesDir . '/components'
      );

    $this->themeExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn([]);

    $this->newSystemUnderTest();

    $expected = [
      'harriet_tubman' => [
        'namespaces' => [
          'harriet_tubman' => [$this->modulesDir . '/tubman/deprecated'],
        ],
        'extensionPath' => $this->modulesDir . '/tubman',
      ],
      'phillis_wheatley' => [
        'namespaces' => [
          'phillis_wheatley' => [$this->modulesDir . '/wheatley/templates'],
          'wheatley' => [$this->modulesDir . '/wheatley/components'],
        ],
        'extensionPath' => $this->modulesDir . '/wheatley',
      ],
      'edna_lewis' => [
        'includedKey' => 'included',
        'namespaces' => [
          'lewis' => [
            $this->modulesDir . '/lewis/templates',
            $this->modulesDir . '/lewis/components',
          ],
        ],
        'extensionPath' => $this->modulesDir . '/lewis',
      ],
      'tracy_chapman' => [
        'namespaces' => [
          'chapman' => [
            $this->modulesDir . '/chapman/templates',
            $this->rootDir . '/libraries/chapman/components',
          ],
        ],
        'extensionPath' => $this->modulesDir . '/chapman',
      ],
      'components' => [
        'allow_default_namespace_reuse' => TRUE,
        'extensionPath' => $this->modulesDir . '/components',
      ],
    ];
    $result = $this->systemUnderTest->getAllModuleInfo();
    $this->assertEquals($expected, $result);

    foreach (['system', 'edna_lewis', 'tracy_chapman'] as $namespace) {
      $this->assertTrue($this->systemUnderTest->isProtectedNamespace($namespace), 'Failed finding "' . $namespace . '" in protected namespaces list.');
    }
    foreach ([
      'harriet_tubman',
      'phillis_wheatley',
      'wheatley',
      'lewis',
      'chapman',
      'components',
    ] as $namespace) {
      $this->assertNotTrue($this->systemUnderTest->isProtectedNamespace($namespace), 'Failed looking up "' . $namespace . '" in protected namespaces list.');
    }
  }

  /**
   * Tests retrieving components info from a module.
   *
   * @covers ::getModuleInfo
   */
  public function testGetModuleInfo() {
    $this->moduleExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn([
        'foo' => [
          'name' => 'Foo',
          'type' => 'module',
          'components' => [
            'included' => 'foo',
          ],
        ],
        'bar' => [
          'name' => 'Bar',
          'type' => 'module',
          'components' => [
            'included' => 'bar',
          ],
        ],
      ]);
    $this->moduleExtensionList
      ->expects($this->exactly(2))
      ->method('getPath')
      ->willReturn($this->modulesDir . '/foo', $this->modulesDir . '/bar');

    $this->themeExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn([]);

    $this->newSystemUnderTest();

    $expected = [
      'included' => 'bar',
      'extensionPath' => $this->modulesDir . '/bar',
    ];
    $result = $this->systemUnderTest->getModuleInfo('bar');
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests retrieving all components info from modules.
   *
   * @covers ::getAllModuleInfo
   */
  public function testGetAllModuleInfo() {
    $this->moduleExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn([
        'foo' => [
          'name' => 'Foo',
          'type' => 'module',
          'no-components' => 'ignored',
        ],
        'bar' => [
          'name' => 'Bar',
          'type' => 'module',
          'components' => [
            'included' => 'not-ignored',
          ],
        ],
      ]);
    $this->moduleExtensionList
      ->expects($this->exactly(2))
      ->method('getPath')
      ->willReturn($this->modulesDir . '/foo', $this->modulesDir . '/bar');

    $this->themeExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn([]);

    $this->newSystemUnderTest();

    $expected = [
      'bar' => [
        'included' => 'not-ignored',
        'extensionPath' => $this->modulesDir . '/bar',
      ],
    ];
    $result = $this->systemUnderTest->getAllModuleInfo();
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests retrieving components info from a theme.
   *
   * @covers ::getThemeInfo
   */
  public function testGetThemeInfo() {
    $this->moduleExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn([]);

    $this->themeExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn([
        'foo' => [
          'name' => 'Foo',
          'type' => 'theme',
          'components' => [
            'included' => 'foo',
          ],
        ],
        'bar' => [
          'name' => 'Bar',
          'type' => 'theme',
          'components' => [
            'included' => 'bar',
          ],
        ],
      ]);
    $this->themeExtensionList
      ->expects($this->exactly(2))
      ->method('getPath')
      ->willReturn($this->themesDir . '/foo', $this->themesDir . '/bar');

    $this->newSystemUnderTest();

    $expected = [
      'included' => 'bar',
      'extensionPath' => $this->themesDir . '/bar',
    ];
    $result = $this->systemUnderTest->getThemeInfo('bar');
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests retrieving all components info from themes.
   *
   * @covers ::getAllThemeInfo
   */
  public function testGetAllThemeInfo() {
    $this->moduleExtensionList
      ->method('getAllInstalledInfo')
      ->willReturn([]);

    $this->themeExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn([
        'foo' => [
          'name' => 'Foo',
          'type' => 'theme',
          'no-components' => 'ignored',
        ],
        'bar' => [
          'name' => 'Bar',
          'type' => 'theme',
          'components' => [
            'included' => 'not-ignored',
          ],
        ],
      ]);
    $this->themeExtensionList
      ->expects($this->exactly(2))
      ->method('getPath')
      ->willReturn($this->themesDir . '/foo', $this->themesDir . '/bar');

    $this->newSystemUnderTest();

    $expected = [
      'bar' => [
        'included' => 'not-ignored',
        'extensionPath' => $this->themesDir . '/bar',
      ],
    ];
    $result = $this->systemUnderTest->getAllThemeInfo();
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests checking for protected namespaces.
   *
   * @param array $moduleInfo
   *   List of module .info.yml data.
   * @param array $themeInfo
   *   List of theme .info.yml data.
   * @param array $expected
   *   Expected data.
   *
   * @covers ::isProtectedNamespace
   *
   * @dataProvider providerTestIsProtectedNamespace
   */
  public function testIsProtectedNamespace(array $moduleInfo, array $themeInfo, array $expected) {
    $this->moduleExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn($moduleInfo);

    $this->themeExtensionList
      ->expects($this->exactly(1))
      ->method('getAllInstalledInfo')
      ->willReturn($themeInfo);

    $this->newSystemUnderTest();

    foreach ($expected as $extension => $value) {
      $actual = $this->systemUnderTest->isProtectedNamespace($extension);
      if ($value) {
        $this->assertTrue($actual, 'Failed checking if isProtectedNamespace("' . $extension . '") was TRUE');
      }
      else {
        $this->assertNotTrue($actual, 'Failed checking if isProtectedNamespace("' . $extension . '") was FALSE');
      }
    }
  }

  /**
   * Provides test data to ::testIsProtectedNamespace().
   *
   * @see testIsProtectedNamespace()
   */
  public function providerTestIsProtectedNamespace(): array {
    return [
      'no components data in info.yml' => [
        'moduleInfo' => [
          'fooModule' => [
            'name' => 'Foo Module',
            'type' => 'module',
            'non-components' => 'value',
          ],
        ],
        'themeInfo' => [
          'fooTheme' => [
            'name' => 'Foo Theme',
            'type' => 'theme',
            'no-components' => 'value',
          ],
        ],
        'expected' => [
          'fooModule' => TRUE,
          'fooTheme' => TRUE,
        ],
      ],
      'auto opt-in if default namespace is used' => [
        'moduleInfo' => [
          'fooModule' => [
            'name' => 'Foo Module',
            'type' => 'module',
            'non-components' => 'value',
            'components' => [
              'namespaces' => [
                'fooModule' => 'fooPath',
              ],
            ],
          ],
        ],
        'themeInfo' => [
          'fooTheme' => [
            'name' => 'Foo Theme',
            'type' => 'theme',
            'no-components' => 'value',
            'components' => [
              'namespaces' => [
                'notFooTheme' => 'fooPath',
              ],
            ],
          ],
        ],
        'expected' => [
          'fooModule' => FALSE,
          'fooTheme' => TRUE,
          'notFooTheme' => FALSE,
        ],
      ],
      'manual opt-in with .info.yml flag' => [
        'moduleInfo' => [
          'fooModule' => [
            'name' => 'Foo Module',
            'type' => 'module',
            'non-components' => 'value',
            'components' => [
              'allow_default_namespace_reuse' => TRUE,
            ],
          ],
        ],
        'themeInfo' => [
          'fooTheme' => [
            'name' => 'Foo Theme',
            'type' => 'theme',
            'components' => [],
          ],
        ],
        'expected' => [
          'fooModule' => FALSE,
          'fooTheme' => TRUE,
        ],
      ],
    ];
  }

}

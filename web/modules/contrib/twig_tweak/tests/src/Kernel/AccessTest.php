<?php

namespace Drupal\Tests\twig_tweak\Kernel;

use Drupal\block\BlockViewBuilder;
use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests for the Twig Tweak access control.
 *
 * @group twig_tweak
 */
class AccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * A node for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $node;

  /**
   * The Twig extension.
   *
   * @var \Drupal\twig_tweak\TwigExtension
   */
  private $twigExtension;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'twig_tweak',
    'twig_tweak_test',
    'node',
    'file',
    'user',
    'system',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system']);

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    $values = [
      'type' => 'article',
      'status' => NodeInterface::PUBLISHED,
      // @see twig_tweak_test_node_access()
      'title' => 'Entity access test',
    ];
    $this->node = Node::create($values);
    $this->node->save();

    $this->twigExtension = $this->container->get('twig_tweak.twig_extension');
  }

  /**
   * Test callback.
   */
  public function testDrupalEntity() {

    // -- Unprivileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->drupalEntity('node', $this->node->id());
    self::assertNull($build);

    // -- Unprivileged user without access check.
    $build = $this->twigExtension->drupalEntity('node', $this->node->id(), NULL, NULL, FALSE);
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
      ],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 2'], ['access content']);

    $build = $this->twigExtension->drupalEntity('node', $this->node->id());
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
        'tag_from_twig_tweak_test_node_access',
      ],
      'contexts' => [
        'user',
        'user.permissions',
      ],
      'max-age' => 50,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user without access check.
    $build = $this->twigExtension->drupalEntity('node', $this->node->id(), NULL, NULL, FALSE);
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
      ],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);
  }

  /**
   * Test callback.
   */
  public function testDrupalField() {

    // -- Unprivileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->drupalField('title', 'node', $this->node->id());
    self::assertNull($build);

    // -- Unprivileged user without access check.
    $build = $this->twigExtension->drupalField('title', 'node', $this->node->id(), 'default', NULL, FALSE);
    self::assertArrayHasKey('#items', $build);
    $expected_cache = [
      'contexts' => [],
      'tags' => ['node:1'],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 2'], ['access content']);

    $build = $this->twigExtension->drupalField('title', 'node', $this->node->id());
    self::assertArrayHasKey('#items', $build);
    $expected_cache = [
      'contexts' => [
        'user',
        'user.permissions',
      ],
      'tags' => [
        'node:1',
        'tag_from_twig_tweak_test_node_access',
      ],
      'max-age' => 50,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user without access check.
    $build = $this->twigExtension->drupalField('title', 'node', $this->node->id(), 'default', NULL, FALSE);
    self::assertArrayHasKey('#items', $build);
    $expected_cache = [
      'contexts' => [],
      'tags' => ['node:1'],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);
  }

  /**
   * Test callback.
   */
  public function testDrupalEntityEditForm() {

    // -- Unprivileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->drupalEntityForm('node', $this->node->id());
    self::assertNull($build);

    // -- Unprivileged user without access check.
    $build = $this->twigExtension->drupalEntityForm('node', $this->node->id(), 'default', [], FALSE);
    self::assertArrayHasKey('form_id', $build);
    $expected_cache = [
      'contexts' => ['user.roles:authenticated'],
      'tags' => [
        'config:core.entity_form_display.node.article.default',
        'node:1',
      ],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 2'], ['access content']);

    $build = $this->twigExtension->drupalEntityForm('node', $this->node->id());
    self::assertArrayHasKey('#form_id', $build);
    $expected_cache = [
      'contexts' => [
        'user',
        'user.permissions',
        'user.roles:authenticated',
      ],
      'tags' => [
        'config:core.entity_form_display.node.article.default',
        'node:1',
        'tag_from_twig_tweak_test_node_access',
      ],
      'max-age' => 50,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user without access check.
    $build = $this->twigExtension->drupalEntityForm('node', $this->node->id(), 'default', [], FALSE);
    self::assertArrayHasKey('#form_id', $build);
    $expected_cache = [
      'contexts' => ['user.roles:authenticated'],
      'tags' => [
        'config:core.entity_form_display.node.article.default',
        'node:1',
      ],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);
  }

  /**
   * Test callback.
   */
  public function testDrupalEntityAddForm() {

    $node_values = ['type' => 'article'];

    // -- Unprivileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->drupalEntityForm('node', NULL, 'default', $node_values);
    self::assertNull($build);

    // -- Unprivileged user without access check.
    $build = $this->twigExtension->drupalEntityForm('node', NULL, 'default', $node_values, FALSE);
    self::assertArrayHasKey('form_id', $build);
    $expected_cache = [
      'contexts' => ['user.roles:authenticated'],
      'tags' => ['config:core.entity_form_display.node.article.default'],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user with access check.
    $this->setUpCurrentUser(
      ['name' => 'User 2'],
      ['access content', 'create article content'],
    );

    $build = $this->twigExtension->drupalEntityForm('node', NULL, 'default', $node_values);
    self::assertArrayHasKey('form_id', $build);
    $expected_cache = [
      'contexts' => [
        'user.permissions',
        'user.roles:authenticated',
      ],
      'tags' => ['config:core.entity_form_display.node.article.default'],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user without access check.
    $build = $this->twigExtension->drupalEntityForm('node', NULL, 'default', $node_values);
    self::assertArrayHasKey('form_id', $build);
    $expected_cache = [
      'contexts' => [
        'user.permissions',
        'user.roles:authenticated',
      ],
      'tags' => ['config:core.entity_form_display.node.article.default'],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);
  }

  /**
   * Test callback.
   *
   * @see \Drupal\twig_tweak_test\Plugin\Block\FooBlock
   */
  public function testDrupalBlock() {

    // -- Privileged user.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->drupalBlock('twig_tweak_test_foo');
    $expected_content = [
      '#markup' => 'Foo',
      '#cache' => [
        'contexts' => ['url'],
        'tags' => ['tag_from_build'],
      ],
    ];
    self::assertSame($expected_content, $build['content']);
    $expected_cache = [
      'contexts' => ['user'],
      'tags' => ['tag_from_blockAccess', 'tag_twig_tweak_test_foo_plugin'],
      'max-age' => 35,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Unprivileged user.
    $this->setUpCurrentUser(['name' => 'User 2']);

    $build = $this->twigExtension->drupalBlock('twig_tweak_test_foo');
    self::assertNull($build);
  }

  /**
   * Test callback.
   */
  public function testDrupalRegion() {

    // @codingStandardsIgnoreStart
    $create_block = function ($id) {
      return new class(['id' => $id], 'block') extends Block {
        public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
          $result = AccessResult::allowedIf($this->id == 'block_1');
          $result->cachePerUser();
          $result->addCacheTags(['tag_for_' . $this->id]);
          $result->setCacheMaxAge(123);
          return $return_as_object ? $result : $result->isAllowed();
        }
        public function getPlugin() {
          return NULL;
        }
      };
    };
    // @codingStandardsIgnoreEnd

    $storage = $this->createMock(EntityStorageInterface::class);
    $blocks = [
      'block_1' => $create_block('block_1'),
      'block_2' => $create_block('block_2'),
    ];
    $storage->expects($this->any())
      ->method('loadByProperties')
      ->willReturn($blocks);

    $view_builder = $this->createMock(BlockViewBuilder::class);
    $content = [
      '#markup' => 'foo',
      '#cache' => [
        'tags' => ['tag_from_view'],
      ],
    ];
    $view_builder->expects($this->any())
      ->method('view')
      ->willReturn($content);
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->any())
      ->method('getListCacheTags')
      ->willReturn([]);
    $entity_type->expects($this->any())
      ->method('getListCacheContexts')
      ->willReturn([]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->any())
      ->method('getStorage')
      ->willReturn($storage);
    $entity_type_manager->expects($this->any())
      ->method('getViewBuilder')
      ->willReturn($view_builder);
    $entity_type_manager->expects($this->any())
      ->method('getDefinition')
      ->willReturn($entity_type);

    $this->container->set('entity_type.manager', $entity_type_manager);

    $build = $this->twigExtension->drupalRegion('bar');
    $expected_build = [
      'block_1' => [
        '#markup' => 'foo',
        '#cache' => [
          'tags' => ['tag_from_view'],
        ],
      ],
      '#region' => 'bar',
      '#theme_wrappers' => ['region'],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'tag_for_block_1',
          'tag_for_block_2',
        ],
        'max-age' => 123,
      ],
    ];
    self::assertSame($expected_build, $build);
  }

  /**
   * Test callback.
   */
  public function testDrupalImage() {

    // @codingStandardsIgnoreStart
    $create_image = function ($uri) {
      $file = new class(['uri' => $uri], 'file') extends File {
        public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
          $is_public = parse_url($this->getFileUri(), PHP_URL_SCHEME) == 'public';
          $result = AccessResult::allowedIf($is_public);
          $result->cachePerUser();
          $result->addCacheTags(['tag_for_' . $this->getFileUri()]);
          $result->setCacheMaxAge(123);
          return $return_as_object ? $result : $result->isAllowed();
        }
        public function getPlugin() {
          return NULL;
        }
      };
      $file->setFileUri($uri);
      return $file;
    };
    // @codingStandardsIgnoreEnd

    $storage = $this->createMock(EntityStorageInterface::class);
    $map = [
      [
        ['uri' => 'public://ocean.jpg'],
        [$create_image('public://ocean.jpg')],
      ],
      [
        ['uri' => 'private://sea.jpg'],
        [$create_image('private://sea.jpg')],
      ],
    ];
    $storage->method('loadByProperties')
      ->will($this->returnValueMap($map));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($storage);

    $this->container->set('entity_type.manager', $entity_type_manager);

    // -- Public image with access check.
    $build = $this->twigExtension->drupalImage('public://ocean.jpg');
    $expected_build = [
      '#uri' => 'public://ocean.jpg',
      '#attributes' => [],
      '#theme' => 'image',
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['tag_for_public://ocean.jpg'],
        'max-age' => 123,
      ],
    ];
    self::assertSame($expected_build, $build);

    // -- Public image without access check.
    $build = $this->twigExtension->drupalImage('public://ocean.jpg', NULL, [], NULL, FALSE);
    $expected_build = [
      '#uri' => 'public://ocean.jpg',
      '#attributes' => [],
      '#theme' => 'image',
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    self::assertSame($expected_build, $build);

    // -- Private image with access check.
    $build = $this->twigExtension->drupalImage('private://sea.jpg');
    self::assertNull($build);

    // -- Private image without access check.
    $build = $this->twigExtension->drupalImage('private://sea.jpg', NULL, [], NULL, FALSE);
    $expected_build = [
      '#uri' => 'private://sea.jpg',
      '#attributes' => [],
      '#theme' => 'image',
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    self::assertSame($expected_build, $build);
  }

  /**
   * Test callback.
   */
  public function testView() {

    // -- Unprivileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 1']);

    $build = $this->twigExtension->view($this->node);
    self::assertNull($build);

    // -- Unprivileged user without access check.
    $build = $this->twigExtension->view($this->node, NULL, NULL, FALSE);
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
      ],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user with access check.
    $this->setUpCurrentUser(['name' => 'User 2'], ['access content']);

    $build = $this->twigExtension->view($this->node, NULL);
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
        'tag_from_twig_tweak_test_node_access',
      ],
      'contexts' => [
        'user',
        'user.permissions',
      ],
      'max-age' => 50,
    ];
    self::assertSame($expected_cache, $build['#cache']);

    // -- Privileged user without access check.
    $build = $this->twigExtension->view($this->node, NULL, NULL, FALSE);
    self::assertArrayHasKey('#node', $build);
    $expected_cache = [
      'tags' => [
        'node:1',
        'node_view',
      ],
      'contexts' => [],
      'max-age' => Cache::PERMANENT,
    ];
    self::assertSame($expected_cache, $build['#cache']);
  }

}

<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\TwigExtension;
use Drupal\Tests\UnitTestCase;
use Twig\Extension\CoreExtension;

/**
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
class TwigExtensionFiltersTest extends UnitTestCase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $renderer;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\TwigExtension
   */
  protected $systemUnderTest;

  /**
   * The Twig CoreExtension.
   *
   * @var \Twig\Extension\CoreExtension
   */
  protected $coreExtension;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->systemUnderTest = new TwigExtension();

    // Load the Twig CoreExtension as its file contains static functions used by
    // TwigExtension.
    $this->coreExtension = new CoreExtension();
  }

  /**
   * Tests exceptions during recursive_merge filter.
   *
   * @covers ::recursiveMergeFilter
   */
  public function testRecursiveMergeFilterException() {
    try {
      TwigExtension::recursiveMergeFilter('not-an-array', ['key' => 'value']);
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('The recursive_merge filter only works on arrays or "Traversable" objects, got "string".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the recursive_merge filter.
   *
   * @param array $element
   *   The element to alter.
   * @param array $value
   *   The value to set.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::recursiveMergeFilter
   *
   * @dataProvider providerTestRecursiveMergeFilter
   */
  public function testRecursiveMergeFilter(array $element, array $value, array $expected) {
    $result = NULL;
    try {
      $result = TwigExtension::recursiveMergeFilter($element, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertEquals($expected, $result, $this->getName());
    $this->assertEquals(array_replace_recursive($element, $value), $result, $this->getName());
  }

  /**
   * Data provider for testRecursiveMergeFilter().
   *
   * @see testRecursiveMergeFilter()
   */
  public function providerTestRecursiveMergeFilter(): array {
    return [
      'Recursively sets values' => [
        'element' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'value' => [
          'extra' => 'extra-value',
          'element' => [
            '#attributes' => [
              'class' => ['new-value'],
              'placeholder' => 'Label',
            ],
          ],
        ],
        'expected' => [
          'existing' => 'value',
          'extra' => 'extra-value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['new-value', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'Label',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests exceptions during set filter.
   *
   * @covers ::setFilter
   */
  public function testSetFilterException() {
    try {
      TwigExtension::setFilter('not-an-array', 'key', 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $needle = 'The set filter only works on arrays or "Traversable" objects, got "string".';
      if (method_exists($this, 'assertStringContainsString')) {
        $this->assertStringContainsString($needle, $e->getMessage());
      }
      else {
        $this->assertContains($needle, $e->getMessage());
      }
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests exceptions during set filter.
   *
   * @covers ::setFilter
   */
  public function testSetFilterMissingArgumentException() {
    try {
      TwigExtension::setFilter(['an-array'], NULL, 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('Value for argument "at" is required for filter "set".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the set filter.
   *
   * @param array $element
   *   The element to alter.
   * @param string|array $at
   *   The dotted-path to the deeply nested element to set. (Or an array value
   *   to merge, if using the backwards-compatible 8.x-2.x syntax.)
   * @param mixed $value
   *   The value to set.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::setFilter
   *
   * @dataProvider providerTestSetFilter
   */
  public function testSetFilter(array $element, $at, $value, array $expected) {
    $result = NULL;
    try {
      $result = TwigExtension::setFilter($element, $at, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertEquals($expected, $result, $this->getName());
  }

  /**
   * Data provider for testSetFilter().
   *
   * @see testSetFilter()
   */
  public function providerTestSetFilter(): array {
    return [
      'Sets a new value' => [
        'element' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'at' => 'element.#attributes.placeholder',
        'value' => 'Label',
        'expected' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'Label',
            ],
          ],
        ],
      ],
      'Replaces a targeted array' => [
        'element' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'at' => 'element.#attributes.class',
        'value' => ['new-value'],
        'expected' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['new-value'],
              'id' => 'element',
            ],
          ],
        ],
      ],
      'Uses 8.x-2.x syntax for backwards-compatibility' => [
        'element' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'at' => [
          'extra' => 'extra-value',
          'element' => [
            '#attributes' => [
              'class' => ['new-value'],
              'placeholder' => 'Label',
            ],
          ],
        ],
        'value' => NULL,
        'expected' => [
          'existing' => 'value',
          'extra' => 'extra-value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['new-value', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'Label',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests exceptions during add filter.
   *
   * @covers ::addFilter
   */
  public function testAddFilterException() {
    try {
      TwigExtension::addFilter('not-an-array', 'key', 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $needle = 'The add filter only works on arrays or "Traversable" objects, got "string".';
      if (method_exists($this, 'assertStringContainsString')) {
        $this->assertStringContainsString($needle, $e->getMessage());
      }
      else {
        $this->assertContains($needle, $e->getMessage());
      }
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests exceptions during add filter.
   *
   * @covers ::addFilter
   */
  public function testAddFilterMissingArgumentException() {
    try {
      TwigExtension::addFilter(['an-array'], NULL, 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('Value for argument "at" is required for filter "add".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the add filter.
   *
   * @param string $at
   *   The dotted-path to the deeply nested element to add.
   * @param mixed $value
   *   The value(s) to add.
   * @param array $expected
   *   The expected render array.
   *
   * @covers ::addFilter
   *
   * @dataProvider providerTestAddFilter
   */
  public function testAddFilter(string $at, $value, array $expected) {
    $element = [
      'existing' => 'value',
      'element' => [
        '#type' => 'element',
        '#attributes' => [
          'class' => ['old-value-1', 'old-value-2'],
          'id' => 'element',
        ],
      ],
    ];

    $result = NULL;
    try {
      $result = TwigExtension::addFilter($element, $at, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertEquals($expected, $result, 'Failed to replace a value.');
  }

  /**
   * Data provider for testAddFilter().
   *
   * @see testAddFilter()
   */
  public function providerTestAddFilter(): array {
    return [
      'replacing a value' => [
        'at' => 'element.#attributes.id',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'new-value',
            ],
          ],
        ],
      ],
      'setting a new property on an existing array' => [
        'at' => 'element.#attributes.placeholder',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'new-value',
            ],
          ],
        ],
      ],
      'targeting an existing array with a string' => [
        'at' => 'element.#attributes.class',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2', 'new-value'],
              'id' => 'element',
            ],
          ],
        ],
      ],
      'targeting an existing array with an array' => [
        'at' => 'element.#attributes.class',
        'value' => ['new-value-1', 'new-value-2'],
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => [
                'old-value-1',
                'old-value-2',
                'new-value-1',
                'new-value-2',
              ],
              'id' => 'element',
            ],
          ],
        ],
      ],
      'targeting a non-existent parent property' => [
        'at' => 'new-element.#attributes.class',
        'value' => ['new-value'],
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
          'new-element' => ['#attributes' => ['class' => ['new-value']]],
        ],
      ],
    ];
  }

}

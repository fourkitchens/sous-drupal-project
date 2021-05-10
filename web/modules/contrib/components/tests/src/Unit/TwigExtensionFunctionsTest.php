<?php

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\TwigExtension;
use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Core\Template\TwigExtension as CoreTwigExtension;
use Drupal\Tests\UnitTestCase;
use Twig\Environment;

/**
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
class TwigExtensionFunctionsTest extends UnitTestCase {

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
   * The Twig environment.
   *
   * @var \Twig\Environment
   */
  protected $twigEnvironment;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $dateFormatter = $this->createMock('\Drupal\Core\Datetime\DateFormatterInterface');

    $this->systemUnderTest = new TwigExtension();
    $coreTwigExtension = new CoreTwigExtension($this->renderer, $urlGenerator, $themeManager, $dateFormatter);

    $loader = new StringLoader();
    $this->twigEnvironment = new Environment($loader);
    $this->twigEnvironment->setExtensions([
      $coreTwigExtension,
      $this->systemUnderTest,
    ]);
  }

  /**
   * Tests incorrectly using a Twig namespaced template name.
   *
   * @covers ::template
   */
  public function testTemplateNamespaceException() {
    $this->renderer->expects($this->exactly(0))
      ->method('render');

    try {
      $this->twigEnvironment->render(
        '{{ template("@stable/item-list.html.twig", items = [ link ] ) }}',
        ['link' => '']
      );
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $needle = 'Templates with namespaces are not supported; "@stable/item-list.html.twig" given.';
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
   * Tests creating #theme render arrays within a Twig template.
   *
   * @param string $template
   *   The inline template to render.
   * @param array $variables
   *   An array of variables to provide to the template.
   * @param array $expected
   *   The render array expected to be returned.
   * @param string $rendered_output
   *   The HTML output from the rendered $expected array.
   *
   * @covers ::template
   *
   * @dataProvider providerTestTemplate
   */
  public function testTemplate(
    string $template,
    array $variables,
    array $expected,
    string $rendered_output
  ) {
    $this->renderer
      ->expects($this->exactly(1))
      ->method('render')
      ->with($expected)
      ->willReturn($rendered_output);

    $result = NULL;
    try {
      $result = $this->twigEnvironment->render($template, $variables);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }
    $this->assertEquals($rendered_output, $result, $this->getName());
  }

  /**
   * Data provider for testTemplate().
   *
   * @see testTemplate()
   */
  public function providerTestTemplate(): array {
    $link = [
      '#type' => 'link',
      '#title' => 'example link',
      '#url' => 'https://example.com',
    ];

    return [
      'Works with template name' => [
        'template' => '{{ template("item-list.html.twig", items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => 'item_list',
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
      'Works with theme hook' => [
        'template' => '{{ template("item_list", items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => 'item_list',
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
      'Works with an array of theme hooks' => [
        'inline_template' => '{{ template([ "item_list__dogs", "item_list__cats" ], items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => ['item_list__dogs', 'item_list__cats'],
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
    ];
  }

}

<?php

namespace Drupal\Tests\components\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the components module in a fully loaded Drupal instance.
 *
 * @group components
 */
class ComponentsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'components',
    'components_test',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'components_test_theme';

  /**
   * Renders a render array.
   *
   * @param array $elements
   *   The elements to render.
   *
   * @return string
   *   The rendered string output (typically HTML).
   */
  protected function render(array &$elements): string {
    return $this->container->get('renderer')->renderRoot($elements);
  }

  /**
   * Ensures component templates can be loaded inside a Drupal instance.
   */
  public function testLoadTemplate() {
    $result = NULL;
    try {
      $element = [
        // The templates/components-test.html.twig file determines which
        // templates are loaded.
        '#theme' => 'components_test',
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected; "' . $e->getMessage() . '" thrown during: ' . $this->getName());
    }

    // The following templates are in paths defined in .info namespace
    // definitions.
    foreach ([
      'This is the "@components_test/components-test.twig" template from the components_test module.',
      'This is the "@components/components-test-active-theme.twig" template from the components_test_theme theme.',
      'This is the "@components/components-test-base-theme.twig" template from the components_test_base_theme theme.',
      'This is the "@components/components-test-module.twig" template from the components_test module.',
    ] as $foundString) {
      if (method_exists($this, 'assertStringContainsString')) {
        $this->assertStringContainsString($foundString, $result);
      }
      else {
        $this->assertContains($foundString, $result);
      }
    }
    // The following templates are in paths defined in .info namespace
    // definitions, but are overridden by the templates above.
    foreach ([
      'This is the "@components/components-test-active-theme.twig" template from the components_test_base_theme theme.',
      'This is the "@components/components-test-active-theme.twig" template from the components_test module.',
      'This is the "@components/components-test-base-theme.twig" template from the components_test module.',
    ] as $notFoundString) {
      if (method_exists($this, 'assertStringNotContainsString')) {
        $this->assertStringNotContainsString($notFoundString, $result);
      }
      else {
        $this->assertContains($notFoundString, $result);
      }
    }

    // This template is found using hook_protected_twig_namespaces_alter().
    $foundString = 'This is the "@system/components-test-protected-twig-namespaces-alter.twig" template from the components_test module.';
    if (method_exists($this, 'assertStringContainsString')) {
      $this->assertStringContainsString($foundString, $result);
    }
    else {
      $this->assertContains($foundString, $result);
    }
  }

}

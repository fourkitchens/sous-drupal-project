<?php

namespace Drupal\Tests\components\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Defines a base class for Components kernel testing.
 */
abstract class ComponentsKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Enable Twig debugging.
    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $container->setParameter('twig.config', $parameters);
  }

  /**
   * {@inheritdoc}
   *
   * We override KernelTestBase::render() so that it outputs Twig debug comments
   * only for the render array given in a test and not for an entire page.
   *
   * @throws \Exception
   */
  protected function render(array &$elements): string {
    // \Drupal\Core\Render\BareHtmlPageRenderer::renderBarePage calls out to
    // system_page_attachments() directly.
    if (!\Drupal::moduleHandler()->moduleExists('system')) {
      throw new \Exception(__METHOD__ . ' requires system module to be installed.');
    }

    return $this->container->get('renderer')->renderRoot($elements);
  }

}

<?php

namespace Drupal\entity_embed\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller which renders a preview of the provided text.
 */
class PreviewController implements ContainerInjectionInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an PreviewController instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Returns a HTML response containing a preview of the text after filtering.
   *
   * Applies all of the given text format's filters, not just the `entity_embed`
   * filter, because for example `filter_align` and `filter_caption` may apply
   * to it as well.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The text format.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The filtered text.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if 'text' parameter is not found in the request.
   *
   * @see \Drupal\editor\EditorController::getUntransformedText
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    $text = $request->get('text');
    if ($text == '') {
      throw new NotFoundHttpException();
    }

    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $filter_format->id(),
    ];
    $html = $this->renderer->renderPlain($build);

    // Note that we intentionally do not use:
    // - \Drupal\Core\Cache\CacheableResponse because caching it on the server
    //   side is wasteful, hence there is no need for cacheability metadata.
    // - \Drupal\Core\Render\HtmlResponse because there is no need for
    //   attachments nor cacheability metadata.
    return (new Response($html))
      // Do not allow any intermediary to cache the response, only the end user.
      ->setPrivate()
      // Allow the end user to cache it for up to 5 minutes.
      ->setMaxAge(300);
  }

}

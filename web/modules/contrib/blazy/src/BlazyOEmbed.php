<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Image\ImageFactory;
use Drupal\file\Entity\File;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides OEmbed integration.
 */
class BlazyOEmbed implements BlazyOEmbedInterface {

  /**
   * Core Media oEmbed url resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * Core Media oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * Core Media oEmbed iframe url helper.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iframeUrlHelper;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The Media oEmbed Resource.
   *
   * @var \Drupal\media\OEmbed\Resource[]
   */
  protected $resource;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(RequestStack $request, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, ImageFactory $image_factory, BlazyManagerInterface $blazy_manager) {
    $this->request = $request;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->iframeUrlHelper = $iframe_url_helper;
    $this->imageFactory = $image_factory;
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('image.factory'),
      $container->get('blazy.manager')
    );
  }

  /**
   * Returns the Media oEmbed resource fecther.
   */
  public function getResourceFetcher() {
    return $this->resourceFetcher;
  }

  /**
   * Returns the Media oEmbed url resolver fecthers.
   */
  public function getUrlResolver() {
    return $this->urlResolver;
  }

  /**
   * Returns the Media oEmbed url resolver fecthers.
   */
  public function getIframeUrlHelper() {
    return $this->iframeUrlHelper;
  }

  /**
   * Returns the image factory.
   */
  public function imageFactory() {
    return $this->imageFactory;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getResource($input_url) {
    if (!isset($this->resource[hash('md2', $input_url)])) {
      $resource_url = $this->urlResolver->getResourceUrl($input_url, 0, 0);
      $this->resource[hash('md2', $input_url)] = $this->resourceFetcher->fetchResource($resource_url);
    }

    return $this->resource[hash('md2', $input_url)];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$settings = []) {
    if (empty($settings['_input_url'])) {
      $this->checkInputUrl($settings);
    }

    // @todo revisit if any issue with other resource types.
    $url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => $settings['input_url'],
        'max_width' => 0,
        'max_height' => 0,
        'hash' => $this->iframeUrlHelper->getHash($settings['input_url'], 0, 0),
        'blazy' => 1,
        'autoplay' => empty($settings['media_switch']) ? 0 : 1,
      ],
    ]);

    if (!empty($settings['iframe_domain'])) {
      $url->setOption('base_url', $settings['iframe_domain']);
    }

    // The top level iframe url relative to the site, or iframe_domain.
    $settings['embed_url'] = $url->toString();
    if (isset($settings['media_source'])) {
      $settings['type'] = $settings['media_source'] == 'oembed:video' ? 'video' : $settings['media_source'];
    }
  }

  /**
   * Checks the given input URL.
   */
  public function checkInputUrl(array &$settings = []) {
    $settings['input_url'] = UrlHelper::stripDangerousProtocols(trim($settings['input_url']));

    // OEmbed Resource doesn't accept `/embed`, provides a conversion helper.
    if (strpos($settings['input_url'], 'youtube.com/embed') !== FALSE) {
      $search = '/youtube\.com\/embed\/([a-zA-Z0-9]+)/smi';
      $replace = "youtube.com/watch?v=$1";
      $settings['input_url'] = preg_replace($search, $replace, $settings['input_url']);
    }
    $settings['_input_url'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutoPlayUrl($url = '') {
    $data = [];
    if (!empty($url)) {
      $data['oembed_url'] = $url;
      // Adds autoplay for media URL on lightboxes, saving another click.
      if (strpos($url, 'autoplay') === FALSE || strpos($url, 'autoplay=0') !== FALSE) {
        $data['autoplay_url'] = strpos($url, '?') === FALSE ? $url . '?autoplay=1' : $url . '&autoplay=1';
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaItem(array &$data, $media) {
    // Only proceed if we do have Media.
    if ($media->getEntityTypeId() != 'media') {
      return;
    }

    BlazyMedia::mediaItem($data, $media);
    $settings = $data['settings'];

    // @todo support local video/ audio file, and other media sources.
    // @todo check for Resource::TYPE_PHOTO, Resource::TYPE_RICH, etc.
    switch ($settings['media_source']) {
      case 'oembed':
      case 'oembed:video':
        // Input url != embed url. For Youtube, /watch != /embed.
        if ($input_url = $media->getSource()->getSourceFieldValue($media)) {
          $settings['input_url'] = $input_url;

          $this->build($settings);
        }
        break;

      case 'image':
        $settings['type'] = 'image';
        break;

      // No special handling for anything else for now, pass through.
      default:
        break;
    }

    // Do not proceed if it has type, already managed by theme_blazy().
    // Supports other Media entities: Facebook, Instagram, local video, etc.
    if (empty($settings['type']) && ($build = BlazyMedia::build($media, $settings))) {
      $data['content'][] = $build;
    }

    // Collect what's needed for clarity.
    $data['settings'] = $settings;
  }

  /**
   * Returns external image item from resource relevant to BlazyFilter.
   */
  public function getExternalImageItem(array &$settings) {
    // Iframe URL may be valid, but not stored as a Media entity.
    if (($resource = $this->getResource($settings['input_url'])) && $resource->getThumbnailUrl()) {
      // All we have here is external images. URI validity is not crucial.
      $settings['uri'] = $settings['image_url'] = $resource->getThumbnailUrl()->getUri();
      $settings['type'] = $resource->getType();
      // Respect hard-coded width and height since no UI for all these here.
      if (empty($settings['width'])) {
        $settings['width'] = $resource->getThumbnailWidth() ?: $resource->getWidth();
        $settings['height'] = $resource->getThumbnailHeight() ?: $resource->getHeight();
      }
      return Blazy::image($settings);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @todo compare and merge with BlazyMedia::imageItem().
   */
  public function getImageItem($file) {
    $data = [];
    $entity = $file;

    /** @var Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $file */
    if (isset($file->entity) && !isset($file->alt)) {
      $entity = $file->entity;
    }

    if ($entity instanceof File) {
      if ($image = $this->imageFactory->get($entity->getFileUri())) {
        BlazyMedia::fakeImageItem($data, $entity, $image);
      }
    }

    return $data;
  }

  /**
   * Overrides variables for media-oembed-iframe.html.twig templates.
   */
  public function preprocessMediaOembedIframe(array &$variables) {
    // Without internet, this may be empty, bail out.
    if (empty($variables['media'])) {
      return;
    }

    // Only needed to autoplay video, and make responsive iframe.
    try {
      // Blazy formatters with oEmbed provide contextual params to the query.
      $request = $this->request->getCurrentRequest();
      $is_blazy = $request->query->getInt('blazy', NULL);
      $is_autoplay = $request->query->getInt('autoplay', NULL);
      $url = $request->query->get('url');

      // Only replace url if it is required by Blazy.
      if ($url && $is_blazy == 1) {
        // Load iframe string as a DOMDocument as alternative to regex.
        $dom = Html::load($variables['media']);
        $iframe = $dom->getElementsByTagName('iframe');

        // Replace old oEmbed url with autoplay support, and save the DOM.
        if ($iframe->length > 0) {
          // Fetches autoplay_url.
          $embed_url = $iframe->item(0)->getAttribute('src');
          $settings = $this->getAutoPlayUrl($embed_url);

          // Only replace if autoplay == 1 for Image to iframe, or lightboxes.
          if ($is_autoplay == 1 && !empty($settings['autoplay_url'])) {
            $iframe->item(0)->setAttribute('src', $settings['autoplay_url']);
          }

          // Make responsive iframe with/ without autoplay.
          // The following ensures iframe does not shrink due to its attributes.
          $iframe->item(0)->setAttribute('height', '100%');
          $iframe->item(0)->setAttribute('width', '100%');
          $dom->getElementsByTagName('body')->item(0)->setAttribute('class', 'is-b-oembed');
          $variables['media'] = $dom->saveHTML();
        }
      }
    }
    catch (\Exception $ignore) {
      // Do nothing, likely local work without internet, or the site is down.
      // No need to be chatty on this.
    }
  }

}

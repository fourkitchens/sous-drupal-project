<?php

namespace Drupal\Tests\blazy\Kernel;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;

/**
 * Tests the Blazy manager methods.
 *
 * @coversDefaultClass \Drupal\blazy\BlazyManager
 * @requires module media
 *
 * @group blazy
 */
class BlazyManagerTest extends BlazyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle = $this->bundle;

    $settings['fields']['field_text_multiple'] = 'text';

    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();
  }

  /**
   * Tests BlazyManager image.
   *
   * @param array $settings
   *   The settings being tested.
   * @param bool $expected_has_responsive_image
   *   Has the responsive image style ID.
   *
   * @covers ::preRenderBlazy
   * @covers \Drupal\blazy\BlazyLightbox::build
   * @covers \Drupal\blazy\BlazyLightbox::buildCaptions
   * @dataProvider providerTestPreRenderImage
   */
  public function testPreRenderImage(array $settings = [], $expected_has_responsive_image = FALSE) {
    $build = $this->data;
    $settings['count'] = $this->maxItems;
    $settings['uri'] = $this->uri;
    $settings['resimage'] = $expected_has_responsive_image ? $this->blazyManager->entityLoad('blazy_responsive_test', 'responsive_image_style') : NULL;
    $build['settings'] = array_merge($build['settings'], $settings);
    $switch_css = str_replace('_', '-', $settings['media_switch']);

    $element = $this->doPreRenderImage($build);

    if ($settings['media_switch'] == 'content') {
      $this->assertEquals($settings['content_url'], $element['#url']);
      $this->assertArrayHasKey('#url', $element);
    }
    else {
      $this->assertArrayHasKey('data-' . $switch_css . '-trigger', $element['#url_attributes']);
      $this->assertArrayHasKey('#url', $element);
    }

    $this->assertEquals($expected_has_responsive_image, !empty($element['#settings']['responsive_image_style_id']));
  }

  /**
   * Provide test cases for ::testPreRenderImage().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestPreRenderImage() {
    $data[] = [
      [
        'content_url'  => 'node/1',
        'media_switch' => 'content',
      ],
      FALSE,
    ];
    $data[] = [
      [
        'lightbox'               => TRUE,
        'media_switch'           => 'photobox',
        'responsive_image_style' => 'blazy_responsive_test',
      ],
      TRUE,
    ];
    $data[] = [
      [
        'box_style'          => 'blazy_crop',
        'box_media_style'    => 'large',
        'box_caption'        => 'custom',
        'box_caption_custom' => '[node:field_text_multiple]',
        'embed_url'          => '//www.youtube.com/watch?v=E03HFA923kw',
        'lightbox'           => TRUE,
        'media_switch'       => 'blazy_test',
        'scheme'             => 'youtube',
        'type'               => 'video',
      ],
      FALSE,
    ];

    return $data;
  }

  /**
   * Tests building Blazy attributes.
   *
   * @param array $settings
   *   The settings being tested.
   * @param bool $use_uri
   *   Whether to provide image URI, or not.
   * @param bool $iframe
   *   Whether to expect an iframe, or not.
   * @param mixed|bool|int $expected
   *   The expected output.
   *
   * @covers \Drupal\blazy\Blazy::preprocessBlazy
   * @covers \Drupal\blazy\Blazy::urlAndDimensions
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @dataProvider providerPreprocessBlazy
   */
  public function testPreprocessBlazy(array $settings, $use_uri, $iframe, $expected) {
    $variables = ['attributes' => []];
    $settings = array_merge($this->getFormatterSettings(), $settings);
    $settings += BlazyDefault::itemSettings();

    $settings['blazy']           = TRUE;
    $settings['lazy']            = 'blazy';
    $settings['image_style']     = 'blazy_crop';
    $settings['thumbnail_style'] = 'thumbnail';
    $settings['uri']             = $use_uri ? $this->uri : '';

    if (!empty($settings['embed_url'])) {
      $settings = array_merge(BlazyDefault::entitySettings(), $settings);
    }

    $variables['element']['#item'] = $this->testItem;
    $variables['element']['#settings'] = $settings;

    Blazy::preprocessBlazy($variables);

    $image = $expected == TRUE ? !empty($variables['image']) : empty($variables['image']);
    $iframe = $iframe == TRUE ? !empty($variables['iframe']) : empty($variables['iframe']);

    $this->assertTrue($image);
    $this->assertTrue($iframe);
  }

  /**
   * Provider for ::testPreprocessBlazy.
   */
  public function providerPreprocessBlazy() {
    $data[] = [
      [
        'background' => FALSE,
      ],
      FALSE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
      ],
      TRUE,
      FALSE,
      TRUE,
    ];
    $data[] = [
      [
        'background' => TRUE,
        'ratio' => 'fluid',
        'sizes' => '100w',
        'width' => 640,
        'height' => 360,
      ],
      TRUE,
      FALSE,
      FALSE,
    ];

    return $data;
  }

  /**
   * Tests responsive image integration.
   *
   * @param string $responsive_image_style_id
   *   The responsive_image_style_id.
   * @param bool $expected
   *   The expected output_image_tag.
   *
   * @dataProvider providerResponsiveImage
   */
  public function testPreprocessResponsiveImage($responsive_image_style_id, $expected) {
    $variables = [
      'item' => $this->testItem,
      'uri' => $this->uri,
      'responsive_image_style_id' => $responsive_image_style_id,
      'width' => 600,
      'height' => 480,
    ];

    template_preprocess_responsive_image($variables);

    $variables['img_element']['#uri'] = $this->uri;

    Blazy::preprocessResponsiveImage($variables);

    $this->assertEquals($expected, $variables['output_image_tag']);
  }

  /**
   * Provider for ::testPreprocessResponsiveImage.
   */
  public function providerResponsiveImage() {
    return [
      'Responsive image with picture 8.x-3' => [
        'blazy_picture_test',
        FALSE,
      ],
      'Responsive image without picture 8.x-3' => [
        'blazy_responsive_test',
        TRUE,
      ],
    ];
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   */
  public function testBlazyManagerMethods() {
    // Tests Blazy attachments.
    $attach = ['blazy' => TRUE, 'media_switch' => 'blazy_test'];

    $attachments = $this->blazyManager->attach($attach);
    $this->assertArrayHasKey('blazy', $attachments['drupalSettings']);
  }

}

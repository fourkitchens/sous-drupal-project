<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;

/**
 * @coversDefaultClass \Drupal\blazy\Blazy
 *
 * @group blazy
 */
class BlazyUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpVariables();
    $this->setUpUnitServices();
    $this->setUpUnitContainer();
    $this->setUpMockImage();
  }

  /**
   * Tests \Drupal\blazy\Blazy::buildIframe.
   *
   * @param array $data
   *   The input data which can be string, or integer.
   * @param mixed|bool|int $expected
   *   The expected output.
   *
   * @covers ::buildIframe
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @dataProvider providerTestBuildIframe
   */
  public function testBuildIframe(array $data, $expected) {
    $variables             = ['attributes' => [], 'image' => []];
    $settings              = BlazyDefault::entitySettings();
    $settings['embed_url'] = '//www.youtube.com/watch?v=E03HFA923kw';
    $settings['type']      = 'video';
    $settings['bundle']    = 'remote_video';

    $variables['settings'] = array_merge($settings, $data);
    Blazy::buildIframe($variables);

    $this->assertNotEmpty($variables[$expected]);
  }

  /**
   * Provide test cases for ::testBuildIframe().
   */
  public function providerTestBuildIframe() {
    return [
      [
        [
          'media_switch' => 'media',
          'ratio' => 'fluid',
        ],
        'iframe',
      ],
      [
        [
          'media_switch' => '',
          'ratio' => '',
          'width' => 640,
          'height' => 360,
        ],
        'iframe',
      ],
    ];
  }

  /**
   * Tests \Drupal\blazy\Blazy::preprocessBlazy.
   *
   * @param array $settings
   *   The settings being tested.
   * @param object $item
   *   Whether to provide image item, or not.
   * @param bool $expected_image
   *   Whether to expect an image, or not.
   * @param bool $expected_iframe
   *   Whether to expect an iframe, or not.
   *
   * @covers \Drupal\blazy\Blazy::preprocessBlazy
   * @covers \Drupal\blazy\Blazy::urlAndDimensions
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @dataProvider providerPreprocessBlazy
   */
  public function testPreprocessBlazy(array $settings, $item, $expected_image, $expected_iframe) {
    $variables = ['attributes' => []];
    $build     = $this->data;
    $settings  = array_merge($build['settings'], $settings);
    $settings += BlazyDefault::itemSettings();

    $settings['blazy']           = TRUE;
    $settings['lazy']            = 'blazy';
    $settings['image_style']     = '';
    $settings['thumbnail_style'] = '';

    if (!empty($settings['embed_url'])) {
      $settings = array_merge(BlazyDefault::entitySettings(), $settings);
    }

    $variables['element']['#item'] = $item == TRUE ? $this->testItem : NULL;
    $variables['element']['#settings'] = $settings;

    Blazy::preprocessBlazy($variables);

    $image = $expected_image == TRUE ? !empty($variables['image']) : empty($variables['image']);
    $iframe = $expected_iframe == TRUE ? !empty($variables['iframe']) : empty($variables['iframe']);

    $this->assertTrue($image);
    $this->assertTrue($iframe);

    $this->assertEquals($settings['blazy'], $variables['settings']['blazy']);
  }

  /**
   * Provider for ::testPreprocessBlazy.
   */
  public function providerPreprocessBlazy() {
    $uri = 'public://example.jpg';

    $data[] = [
      [
        'background' => FALSE,
        'uri' => '',
      ],
      TRUE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => TRUE,
        'uri' => $uri,
      ],
      TRUE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
        'ratio' => 'fluid',
        'sizes' => '100w',
        'width' => 640,
        'height' => 360,
        'uri' => $uri,
      ],
      TRUE,
      TRUE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
        'embed_url' => '//www.youtube.com/watch?v=E03HFA923kw',
        'media_switch' => 'media',
        'ratio' => 'fluid',
        'sizes' => '100w',
        'scheme' => 'youtube',
        'type' => 'video',
        'uri' => $uri,
        'use_media' => TRUE,
      ],
      TRUE,
      TRUE,
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests BlazyManager image with lightbox support.
   *
   * This is here as we need file_create_url() for both Blazy and its lightbox.
   *
   * @param array $settings
   *   The settings being tested.
   *
   * @covers \Drupal\blazy\BlazyManager::preRenderBlazy
   * @covers \Drupal\blazy\BlazyLightbox::build
   * @covers \Drupal\blazy\BlazyLightbox::buildCaptions
   * @dataProvider providerTestPreRenderImageLightbox
   */
  public function todoTestPreRenderImageLightbox(array $settings = []) {
    $build                       = $this->data;
    $settings                   += BlazyDefault::itemSettings();
    $settings['count']           = $this->maxItems;
    $settings['uri']             = $this->uri;
    $settings['box_style']       = '';
    $settings['box_media_style'] = '';
    $build['settings']           = array_merge($build['settings'], $settings);
    $switch_css                  = str_replace('_', '-', $settings['media_switch']);

    foreach (['caption', 'media', 'wrapper'] as $key) {
      $build['settings'][$key . '_attributes']['class'][] = $key . '-test';
    }

    $element = $this->doPreRenderImage($build);

    if ($settings['media_switch'] == 'content') {
      $this->assertEquals($settings['content_url'], $element['#url']);
      $this->assertArrayHasKey('#url', $element);
    }
    else {
      $this->assertArrayHasKey('data-' . $switch_css . '-trigger', $element['#url_attributes']);
      $this->assertArrayHasKey('#url', $element);
    }
  }

  /**
   * Provide test cases for ::testPreRenderImageLightbox().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestPreRenderImageLightbox() {
    $data[] = [
      [
        'box_caption' => '',
        'content_url' => 'node/1',
        'dimension' => '',
        'lightbox' => FALSE,
        'media_switch' => 'content',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'auto',
        'lightbox' => TRUE,
        'media_switch' => 'colorbox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'alt',
        'lightbox' => TRUE,
        'media_switch' => 'colorbox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'title',
        'lightbox' => TRUE,
        'media_switch' => 'photobox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'alt_title',
        'lightbox' => TRUE,
        'media_switch' => 'colorbox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'title_alt',
        'lightbox' => TRUE,
        'media_switch' => 'photobox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'entity_title',
        'lightbox' => TRUE,
        'media_switch' => 'photobox',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'custom',
        'box_caption_custom' => '[node:field_text_multiple]',
        'dimension' => '640x360',
        'embed_url' => '//www.youtube.com/watch?v=E03HFA923kw',
        'lightbox' => TRUE,
        'media_switch' => 'photobox',
        'scheme' => 'youtube',
        'type' => 'video',
      ],
    ];

    return $data;
  }

}

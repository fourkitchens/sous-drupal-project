<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyGrid;

/**
 * @coversDefaultClass \Drupal\blazy\BlazyGrid
 *
 * @group blazy
 */
class BlazyGridUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;

  /**
   * Tests \Drupal\blazy\BlazyGrid::build().
   *
   * @covers ::build
   */
  public function testBuild() {
    $settings                    = BlazyDefault::htmlSettings();
    $settings['grid']            = 4;
    $settings['grid_medium']     = 3;
    $settings['grid_small']      = 2;
    $settings['grid_item_class'] = 'grid--test';
    $settings['image_style']     = 'blazy_crop';
    $settings['media_switch']    = 'media';
    $settings['style']           = 'grid';
    $settings['type']            = 'image';

    $items = [];
    foreach (range(1, 3) as $key) {
      $items[] = ['#markup' => '<img src="/core/misc/druplicon.png" alt="thumbnail ' . $key . '">'];
    }

    $element = BlazyGrid::build($items, $settings);
    $this->assertEquals('item_list', $element['#theme']);
  }

}

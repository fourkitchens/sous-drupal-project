<?php

namespace Drupal\Tests\blazy\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyCreationTestTrait;

/**
 * Tests the Blazy Filter JavaScript using Selenium, or Chromedriver.
 *
 * @group blazy
 */
class BlazyFilterJavaScriptTest extends WebDriverTestBase {

  use BlazyUnitTestTrait;
  use BlazyCreationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $minkDefaultDriverClass = DrupalSelenium2Driver::class;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'filter',
    'image',
    'media',
    'node',
    'text',
    'blazy',
    'blazy_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpVariables();

    $this->root                   = $this->container->get('app.root');
    $this->fileSystem             = $this->container->get('file_system');
    $this->entityFieldManager     = $this->container->get('entity_field.manager');
    $this->formatterPluginManager = $this->container->get('plugin.manager.field.formatter');
    $this->blazyAdmin             = $this->container->get('blazy.admin');
    $this->blazyOembed            = $this->container->get('blazy.oembed');
    $this->blazyManager           = $this->container->get('blazy.manager');
    $this->testPluginId           = 'blazy_filter';
    $this->maxParagraphs          = 280;

    // Create a text format.
    $full_html = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 0,
    ]);
    $full_html->save();

    // Enable the Blazy filter.
    $this->filterFormatFull = FilterFormat::load('full_html');
    $this->filterFormatFull->setFilterConfig('blazy_filter', [
      'status' => TRUE,
    ]);
    $this->filterFormatFull->save();

    $this->setUpRealImage();
  }

  /**
   * Test the Blazy filter has media-wrapper--blazy for IMG and IFRAME elements.
   */
  public function testFilterDisplay() {
    $image_path = $this->getImagePath(TRUE);

    // Prevents execessive width with aspect ratio.
    $settings['extra_text'] = '<div style="width: 640px;">';
    $settings['extra_text'] .= '<img data-unblazy src="' . $this->url . '" width="320" height="320" />';
    $settings['extra_text'] .= '<iframe src="https://www.youtube.com/watch?v=uny9kbh4iOEd" width="640" height="360"></iframe>';
    $settings['extra_text'] .= '<img src="' . $this->url . '" width="320" height="320" />';
    $settings['extra_text'] .= '<img src="https://www.drupal.org/files/project-images/slick-carousel-drupal.png" width="215" height="162" />';
    $settings['extra_text'] .= '</div>';

    $this->setUpContentTypeTest($this->bundle);
    $this->setUpContentWithItems($this->bundle, $settings);

    $session = $this->getSession();

    $this->drupalGet('node/' . $this->entity->id());

    // Ensures Blazy is not loaded on page load.
    // @todo with Native lazyload, b-loaded is enforced on page load. And
    // since the testing browser Chrome support it, it is irrelevant.
    // @todo $this->assertSession()->elementNotExists('css', '.b-loaded');
    // Capture the initial page load moment.
    $this->createScreenshot($image_path . '/1_blazy_filter_initial.png');
    $this->assertSession()->elementExists('css', '.b-lazy');

    // Trigger Blazy to load images by scrolling down window.
    $session->executeScript('window.scrollTo(0, document.body.scrollHeight);');

    // Capture the loading moment after scrolling down the window.
    $this->createScreenshot($image_path . '/2_blazy_filter_loading.png');

    // Verifies that our filter works identified by media-wrapper--blazy class.
    $this->assertSession()->elementExists('css', '.media-wrapper--blazy');
    $this->assertSession()->elementContains('css', '.media-wrapper--blazy', 'b-lazy');

    // Also verifies that [data-unblazy] should not be touched, nor lazyloaded.
    $this->assertSession()->elementNotContains('css', '.media-wrapper--blazy', 'data-unblazy');

    // Verifies that one of the images is there once loaded.
    $result = $this->assertSession()->waitForElement('css', '.b-loaded');
    $this->assertNotEmpty($result);

    // Capture the loaded moment.
    // The screenshots are at sites/default/files/simpletest/blazy.
    $this->createScreenshot($image_path . '/3_blazy_filter_loaded.png');
  }

}

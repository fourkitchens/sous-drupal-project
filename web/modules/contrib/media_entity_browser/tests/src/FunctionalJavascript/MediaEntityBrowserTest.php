<?php

namespace Drupal\Tests\media_entity_browser\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * A test for the media entity browser.
 *
 * @group media_entity_browser
 */
class MediaEntityBrowserTest extends WebDriverTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'inline_entity_form',
    'entity_browser',
    'entity_browser_entity_form',
    'media_entity_browser',
    'video_embed_media',
    'ctools',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(array_keys($this->container->get('user.permissions')->getPermissions())));
    $this->createMediaType('video_embed_field', [
      'label' => 'Video',
      'id' => 'video',
    ]);

    Media::create([
      'bundle' => 'video',
      'field_media_video_embed_field' => [['value' => 'https://www.youtube.com/watch?v=JQFKVbfqz7w']],
    ])->save();
  }

  /**
   * Test the media entity browser.
   */
  public function testMediaBrowser() {
    $this->drupalGet('entity-browser/iframe/media_entity_browser');
    $this->clickLink('Choose existing media');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', '.view-media-entity-browser-view');
    $thumbnail = $this->assertSession()->elementExists('css', '.views-row img');
    $this->assertStringContainsString('media_entity_browser_thumbnail', $thumbnail->getAttribute('src'));

    $this->assertSession()->elementNotExists('css', '.views-row.checked');
    $this->getSession()->getPage()->find('css', '.views-row')->press();
    $this->assertSession()->elementExists('css', '.views-row.checked');

    $this->assertSession()->buttonExists('Select media');
  }

}

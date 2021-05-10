<?php

namespace Drupal\Tests\media_entity_browser_media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * A test for the media entity browser with media library.
 *
 * @group media_entity_browser
 */
class MediaEntityBrowserMediaLibraryTest extends WebDriverTestBase {

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
    'media_entity_browser_media_library',
    'media_library',
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
    $this->drupalGet('entity-browser/iframe/media_entity_browser_media_library');
    $this->clickLink('Choose existing media');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->elementExists('css', '.media-library-view');
    $this->assertSession()->elementExists('css', '.media-library-item');

    $this->assertSession()->elementNotExists('css', '.js-click-to-select.checked');
    $this->getSession()->getPage()->find('css', '.js-click-to-select input[type=checkbox]')->press();
    $this->assertNotNull($this->assertSession()->waitForElement('css', '.js-click-to-select.checked'));
  }

}

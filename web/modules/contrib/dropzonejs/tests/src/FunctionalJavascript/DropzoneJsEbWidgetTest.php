<?php

namespace Drupal\Tests\dropzonejs\FunctionalJavascript;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Test dropzonejs EB Widget.
 *
 * @group dropzonejs
 */
class DropzoneJsEbWidgetTest extends DropzoneJsWebDriverTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'media',
    'menu_ui',
    'path',
    'dropzonejs_test',
  ];

  /**
   * Permissions for user that will be logged-in for test.
   *
   * @var array
   */
  protected static $userPermissions = [
    'access dropzonejs_eb_test entity browser pages',
    'create dropzonejs_test content',
    'dropzone upload files',
    'access content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $account = $this->drupalCreateUser(static::$userPermissions);
    $this->drupalLogin($account);
  }

  /**
   * Tests the add widget with iframe form.
   */
  public function testUploadFile() {
    $this->drupalGet('node/add/dropzonejs_test');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_dropzonejs_eb_test');
    $this->dropFile();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select entities');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    // For some reason we have to wait here for the markup to show up regardless
    // of the waitForAjaxToFinish above.
    sleep(2);
    $this->assertSession()->elementContains('xpath', '//div[contains(@class, "entities-list")]/div[contains(@class, "label")]', 'notalama.jpg');
  }
}

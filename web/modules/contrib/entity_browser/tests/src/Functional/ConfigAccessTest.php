<?php

namespace Drupal\Tests\entity_browser\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the config UI for adding and editing entity browsers.
 *
 * @group entity_browser
 *
 * @package Drupal\Tests\entity_browser\FunctionalJavascript
 */
class ConfigAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_browser',
    'block',
    'node',
    'taxonomy',
    'views',
  ];

  /**
   * The test administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    $this->adminUser = $this->drupalCreateUser([
      'administer entity browsers',
    ]);

  }

  /**
   * Tests Access to EntityBrowserEditForm.
   */
  public function testEntityBrowserEditFormAccess() {
    // Test that anonymous user can't access admin pages.
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertSession()->statusCodeEquals(403, "Anonymous user can't access entity browser listing page.");
    $this->drupalGet('/admin/config/content/entity_browser/add');
    $this->assertSession()->statusCodeEquals(403, "Anonymous user can't access entity browser add form.");

    // Test that user with "administer entity browsers" permission can access
    // admin pages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertSession()->statusCodeEquals(200, 'Admin user is able to navigate to the entity browser listing page.');
    $this->assertSession()->responseContains('There are no entity browser entities yet.');

    $this->clickLink('Add Entity browser');
    $this->assertSession()->fieldExists('label')->setValue('Test entity browser');
    $this->assertSession()->fieldExists('name')->setValue('test_entity_browser');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->addressEquals('/admin/config/content/entity_browser/test_entity_browser/widgets');

    $this->drupalLogout();
    $this->drupalGet('/admin/config/content/entity_browser/test_entity_browser');
    $this->assertSession()->statusCodeEquals(404, "Anonymous user can't access entity browser edit form.");

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->clickLink('Delete');
    $this->assertSession()->responseContains('This action cannot be undone.', 'Delete question found.');
    $this->drupalPostForm(NULL, [], 'Delete Entity Browser');

    $this->assertSession()->responseContains('Entity browser <em class="placeholder">Test entity browser</em> was deleted.', 'Confirmation message found.');
    $this->assertSession()->responseContains('There are no entity browser entities yet.', 'Entity browsers table is empty.');
    $this->drupalLogout();
  }

}

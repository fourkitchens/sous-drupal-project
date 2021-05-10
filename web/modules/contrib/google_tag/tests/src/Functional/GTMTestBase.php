<?php

namespace Drupal\Tests\google_tag\Functional;

use Drupal\google_tag\Entity\Container;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Google Tag Manager.
 *
 * @todo
 * Use the settings form to save configuration and create snippet files.
 * Confirm snippet file and page response contents.
 * Test further the snippet insertion conditions.
 *
 * @group GoogleTag
 */
abstract class GTMTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['google_tag'];

  /**
   * The snippet file types.
   *
   * @var array
   */
  protected $types = ['script', 'noscript'];

  /**
   * The snippet base URI.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The non-admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonAdminUser;

  /**
   * The test variables.
   *
   * @var array
   */
  protected $variables = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->defaultTheme = 'stark';
    parent::setUp();
    $this->basePath = $this->config('google_tag.settings')->get('uri');
  }

  /**
   * Test the module.
   */
  public function testModule() {
    try {
      $this->modifySettings();
      // Create containers in code.
      $this->createData();
      $this->saveContainers();
      $this->checkSnippetFiles();
      $this->checkPageResponse();
      // Delete containers.
      $this->deleteContainers();
      // Create containers in user interface.
      $this->submitContainers();
      $this->checkSnippetFiles();
      $this->checkPageResponse();
    }
    catch (\Exception $e) {
      parent::assertTrue(TRUE, t('Inside CATCH block'));
      watchdog_exception('gtm_test', $e);
    }
    finally {
      parent::assertTrue(TRUE, t('Inside FINALLY block'));
    }
  }

  /**
   * Modify settings for test purposes.
   */
  protected function modifySettings() {
    // Modify default settings.
    // These should propagate to each container created in test.
    $config = $this->config('google_tag.settings');
    $settings = $config->get();
    unset($settings['_core']);
    $settings['flush_snippets'] = 1;
    $settings['debug_output'] = 1;
    $settings['_default_container']['role_toggle'] = 'include listed';
    $settings['_default_container']['role_list'] = ['content viewer' => 'content viewer'];
    $config->setData($settings)->save();
  }

  /**
   * Create test data: configuration variables and users.
   */
  protected function createData() {
    // Create an admin user.
    $this->drupalCreateRole(['access content', 'administer google tag manager'], 'admin user');
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->roles[] = 'admin user';
    $this->adminUser->save();

    // Create a test user.
    $this->drupalCreateRole(['access content'], 'content viewer');
    $this->nonAdminUser = $this->drupalCreateUser();
    $this->nonAdminUser->roles[] = 'content viewer';
    $this->nonAdminUser->save();
  }

  /**
   * Save containers in the database and create snippet files.
   */
  protected function saveContainers() {
    foreach ($this->variables as $key => $variables) {
      // Create container with default container settings, then modify.
      $container = new Container([], 'google_tag_container');
      $container->enforceIsNew();
      $container->set('id', $variables->id);
      // @todo This has unintended collateral effect; the id property is gone forever.
      // Code in submitContainers() needs this value.
      $values = (array) $variables;
      unset($values['id']);
      array_walk($values, function ($value, $key) use ($container) {
        $container->$key = $value;
      });
      // Save container.
      $container->save();

      // Create snippet files.
      $manager = $this->container->get('google_tag.container_manager');
      $manager->createAssets($container);
    }
  }

  /**
   * Delete containers from the database and delete snippet files.
   */
  protected function deleteContainers() {
    // Delete containers.
    foreach ($this->variables as $key => $variables) {
      // Also exposed as \Drupal::entityTypeManager().
      $container = $this->container->get('entity_type.manager')->getStorage('google_tag_container')->load($key);
      $container->delete();
    }

    // Confirm no containers.
    $manager = $this->container->get('google_tag.container_manager');
    $ids = $manager->loadContainerIDs();
    $message = 'No containers found after delete';
    parent::assertTrue(empty($ids), $message);

    // @todo Next statement will not delete files as containers are gone.
    // $manager->createAllAssets();
    // Delete snippet files.
    $directory = $this->config('google_tag.settings')->get('uri');
    if ($this->config('google_tag.settings')->get('flush_snippets')) {
      if (!empty($directory)) {
        // Remove any stale files (e.g. module update or machine name change).
        $this->container->get('file_system')->deleteRecursive($directory . '/google_tag');
      }
    }

    // Confirm no snippet files.
    $message = 'No snippet files found after delete';
    parent::assertTrue(!is_dir($directory . '/google_tag'), $message);
  }

  /**
   * Add containers through user interface.
   */
  protected function submitContainers() {
    $this->drupalLogin($this->adminUser);

    foreach ($this->variables as $key => $variables) {
      $edit = (array) $variables;
      $this->drupalPostForm('/admin/config/system/google-tag/add', $edit, 'Save');

      $text = 'Created @count snippet files for %container container based on configuration.';
      $args = ['@count' => 3, '%container' => $variables->label];
      $text = t($text, $args);
      $this->assertSession()->responseContains($text);

      $text = 'Created @count snippet files for @container container based on configuration.';
      $args = ['@count' => 3, '@container' => $variables->label];
      $text = t($text, $args);
      $this->assertSession()->pageTextContains($text);
    }
  }

  /**
   * Inspect the snippet files.
   */
  protected function checkSnippetFiles() {
  }

  /**
   * Verify the snippet file contents.
   */
  protected function verifyScriptSnippet($contents, $variables) {
    $status = strpos($contents, "'$variables->container_id'") !== FALSE;
    $message = 'Found in script snippet file: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in script snippet file: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in script snippet file: environment_token';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the snippet file contents.
   */
  protected function verifyNoScriptSnippet($contents, $variables) {
    $status = strpos($contents, "id=$variables->container_id") !== FALSE;
    $message = 'Found in noscript snippet file: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in noscript snippet file: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in noscript snippet file: environment_token';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the snippet file contents.
   */
  protected function verifyDataLayerSnippet($contents, $variables) {
  }

  /**
   * Inspect the page response.
   */
  protected function checkPageResponse() {
    $this->drupalLogin($this->nonAdminUser);
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyScriptTag($realpath) {
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $text = "src=\"$realpath?$query_string\"";
    $this->assertSession()->responseContains($text);

    $xpath = "//script[@src=\"$realpath?$query_string\"]";
    $elements = $this->xpath($xpath);
    $status = !empty($elements);
    $message = 'Found script tag in page response';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyNoScriptTag($realpath, $variables) {
    // The tags are sorted by weight.
    $index = isset($variables->weight) ? $variables->weight - 1 : 0;
    $xpath = '//noscript//iframe';
    $elements = $this->xpath($xpath);
    $contents = $elements[$index]->getAttribute('src');

    $status = strpos($contents, "id=$variables->container_id") !== FALSE;
    $message = 'Found in noscript tag: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in noscript tag: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in noscript tag: environment_token';
    parent::assertTrue($status, $message);
  }

}

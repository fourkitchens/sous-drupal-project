<?php

namespace Drupal\taxonomy_manager\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the configuration form.
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerConfigTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['taxonomy_manager'];

  /**
   * Tests configuration options of the taxonomy_manager module.
   */
  public function testTaxonomyManagerConfiguration() {
    // Create a user with permission to administer taxonomy.
    $user = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($user);

    // Make a POST request to
    // admin/config/user-interface/taxonomy-manager-settings.
    $edit = [];
    $edit['taxonomy_manager_disable_mouseover'] = '1';
    $edit['taxonomy_manager_pager_tree_page_size'] = '50';
    $this->drupalPostForm('admin/config/user-interface/taxonomy-manager-settings', $edit, $this->t('Save configuration'));
    $this->assertResponse(200);
    $this->assertText($this->t('The configuration options have been saved.'), "Saving configuration options successfully.");

  }

}

<?php

namespace Drupal\taxonomy_manager\Tests;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * All pages of the module are accessible. (Routing and menus are OK)
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerPagesTest extends BrowserTestBase {
  use TaxonomyTestTrait;

  /**
   * Vocabulary object.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * Administrator user object.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['taxonomy_manager'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer taxonomy']);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Configuration page is accessible.
   */
  public function testConfigurationPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/config");
    $this->assertResponse(200);
    $this->assertRaw("Advanced settings for the Taxonomy Manager", "The settings page is accessible.");
    $this->drupalLogout();
  }

  /**
   * The page listing vocabularies is accessible.
   */
  public function testVocabulariesListIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/structure");
    $this->assertResponse(200);
    $this->assertRaw("Administer vocabularies with the Taxonomy Manager", "The link to the page listing vocabularies is accessible.");

    $this->drupalGet("admin/structure/taxonomy_manager/voc");
    $this->assertResponse(200);
    $this->assertRaw("Edit vocabulary settings", "The page listing vocabularies is accessible.");
    $this->drupalLogout();
  }

  /**
   * The page with term editing UI is accessible.
   */
  public function testTermsEditingPageIsAccessible() {
    $this->drupalLogin($this->adminUser);
    $voc_name = $this->vocabulary->label();
    // Check admin/structure/taxonomy_manager/voc/{$new_voc_name}.
    $this->drupalGet("admin/structure/taxonomy_manager/voc/$voc_name");
    $this->assertResponse(200);
    $this->assertRaw("Taxonomy Manager - $voc_name", "The taxonomy manager form for editing terms is accessible.");
    $this->drupalLogout();
  }

}

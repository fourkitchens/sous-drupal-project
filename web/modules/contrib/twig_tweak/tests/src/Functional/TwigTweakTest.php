<?php

namespace Drupal\Tests\twig_tweak\Functional;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Core\Render\Markup;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;

/**
 * A test for Twig extension.
 *
 * @group twig_tweak
 */
class TwigTweakTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'twig_tweak',
    'twig_tweak_test',
    'views',
    'node',
    'block',
    'image',
    'responsive_image',
    'language',
    'contextual',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $test_files = $this->getTestFiles('image');

    $image_file = File::create([
      'uri' => $test_files[0]->uri,
      'uuid' => 'b2c22b6f-7bf8-4da4-9de5-316e93487518',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $image_file->save();

    $media_file = File::create([
      'uri' => $test_files[8]->uri,
      'uuid' => '5dd794d0-cb75-4130-9296-838aebc1fe74',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $media_file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Image 1',
      'field_media_image' => ['target_id' => $media_file->id()],
    ]);
    $media->save();

    $node_values = [
      'title' => 'Alpha',
      'field_image' => [
        'target_id' => $image_file->id(),
        'alt' => 'Alt text',
        'title' => 'Title',
      ],
      'field_media' => [
        'target_id' => $media->id(),
      ],
    ];

    $this->createNode($node_values);
    $this->createNode(['title' => 'Beta']);
    $this->createNode(['title' => 'Gamma']);

    ResponsiveImageStyle::create([
      'id' => 'example',
      'label' => 'Example',
      'breakpoint_group' => 'responsive_image',
    ])->save();

    // Setup Russian.
    ConfigurableLanguage::createFromLangcode('ru')->save();
  }

  /**
   * Tests output produced by the Twig extension.
   */
  public function testOutput() {
    // Title block rendered through drupal_region() is cached by some reason.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['block_view']);
    $this->drupalGet('<front>');

    // -- Test default views display.
    $xpath = '//div[@class = "tt-view-default"]';
    $xpath .= '//div[contains(@class, "view-twig-tweak-test") and contains(@class, "view-display-id-default")]';
    $xpath .= '/div[@class = "view-content"]//ul[count(./li) = 3]/li';
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/1") and text() = "Alpha"]');
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/2") and text() = "Beta"]');
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/3") and text() = "Gamma"]');

    // -- Test page_1 view display.
    $xpath = '//div[@class = "tt-view-page_1"]';
    $xpath .= '//div[contains(@class, "view-twig-tweak-test") and contains(@class, "view-display-id-page_1")]';
    $xpath .= '/div[@class = "view-content"]//ul[count(./li) = 3]/li';
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/1") and text() = "Alpha"]');
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/2") and text() = "Beta"]');
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/3") and text() = "Gamma"]');

    // -- Test view argument.
    $xpath = '//div[@class = "tt-view-page_1-with-argument"]';
    $xpath .= '//div[contains(@class, "view-twig-tweak-test")]';
    $xpath .= '/div[@class = "view-content"]//ul[count(./li) = 1]/li';
    $this->assertByXpath($xpath . '//a[contains(@href, "/node/1") and text() = "Alpha"]');

    // -- Test view result.
    $xpath = '//div[@class = "tt-view-result" and text() = 3]';
    $this->assertByXpath($xpath);

    // -- Test block.
    $xpath = '//div[@class = "tt-block"]';
    $xpath .= '/img[contains(@src, "/core/themes/classy/logo.svg") and @alt="Home"]';
    $this->assertByXpath($xpath);

    // -- Test block with wrapper.
    $xpath = '//div[@class = "tt-block-with-wrapper"]';
    $xpath .= '/div[@class = "block block-system block-system-branding-block"]';
    $xpath .= '/h2[text() = "Branding"]';
    $xpath .= '/following-sibling::a[img[contains(@src, "/core/themes/classy/logo.svg") and @alt="Home"]]';
    $xpath .= '/following-sibling::div[@class = "site-name"]/a';
    $this->assertByXpath($xpath);

    // -- Test region.
    $xpath = '//div[@class = "tt-region"]/div[@class = "region region-sidebar-first"]';
    $xpath .= '/div[contains(@class, "block-page-title-block") and h1[@class="page-title" and text() = "Log in"]]';
    $xpath .= '/following-sibling::div[contains(@class, "block-system-powered-by-block")]/span[. = "Powered by Drupal"]';
    $this->assertByXpath($xpath);

    // -- Test entity default view mode.
    $xpath = '//div[@class = "tt-entity-default"]';
    $xpath .= '/article[contains(@class, "node") and not(contains(@class, "node--view-mode-teaser"))]';
    $xpath .= '/h2/a/span[text() = "Alpha"]';
    $this->assertByXpath($xpath);

    // -- Test entity teaser view mode.
    $xpath = '//div[@class = "tt-entity-teaser"]';
    $xpath .= '/article[contains(@class, "node") and contains(@class, "node--view-mode-teaser")]';
    $xpath .= '/h2/a/span[text() = "Alpha"]';
    $this->assertByXpath($xpath);

    // -- Test loading entity from URL.
    $xpath = '//div[@class = "tt-entity-from-url" and not(text())]';
    $this->assertByXpath($xpath);
    $this->drupalGet('/node/2');
    $xpath = '//div[@class = "tt-entity-from-url"]';
    $xpath .= '/article[contains(@class, "node")]';
    $xpath .= '/h2/a/span[text() = "Beta"]';
    $this->assertByXpath($xpath);

    // -- Test access to entity add form.
    $xpath = '//div[@class = "tt-entity-add-form"]/form';
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // -- Test access to entity edit form.
    $xpath = '//div[@class = "tt-entity-edit-form"]/form';
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Grant require permissions and test the forms again.
    $permissions = ['create page content', 'edit any page content'];
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(Role::ANONYMOUS_ID);
    $this->grantPermissions($role, $permissions);
    $this->drupalGet('/node/2');

    // -- Test entity add form.
    $xpath = '//div[@class = "tt-entity-add-form"]/form';
    $xpath .= '//input[@name = "title[0][value]" and @value = ""]';
    $xpath .= '/../../../div/input[@type = "submit" and @value = "Save"]';
    $this->assertByXpath($xpath);

    // -- Test entity edit form.
    $xpath = '//div[@class = "tt-entity-edit-form"]/form';
    $xpath .= '//input[@name = "title[0][value]" and @value = "Alpha"]';
    $xpath .= '/../../../div/input[@type = "submit" and @value = "Save"]';
    $this->assertByXpath($xpath);

    // -- Test field.
    $xpath = '//div[@class = "tt-field"]/div[contains(@class, "field--name-body")]/p[text() != ""]';
    $this->assertByXpath($xpath);

    // -- Test menu (default).
    $xpath = '//div[@class = "tt-menu-default"]/ul[@class = "menu"]/li/a[text() = "Link 1"]/../ul[@class = "menu"]/li/ul[@class = "menu"]/li/a[text() = "Link 3"]';
    $this->assertByXpath($xpath);

    // -- Test menu (level).
    $xpath = '//div[@class = "tt-menu-level"]/ul[@class = "menu"]/li/a[text() = "Link 2"]/../ul[@class = "menu"]/li/a[text() = "Link 3"]';
    $this->assertByXpath($xpath);

    // -- Test menu (depth).
    $xpath = '//div[@class = "tt-menu-depth"]/ul[@class = "menu"]/li[not(ul)]/a[text() = "Link 1"]';
    $this->assertByXpath($xpath);

    // -- Test form.
    $xpath = '//div[@class = "tt-form"]/form[@class="system-cron-settings"]/input[@type = "submit" and @value = "Run cron"]';
    $this->assertByXpath($xpath);

    // -- Test image by FID.
    $xpath = '//div[@class = "tt-image-by-fid"]/img[contains(@src, "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test image by URI.
    $xpath = '//div[@class = "tt-image-by-uri"]/img[contains(@src, "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test image by UUID.
    $xpath = '//div[@class = "tt-image-by-uuid"]/img[contains(@src, "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test image with style.
    $xpath = '//div[@class = "tt-image-with-style"]/img[contains(@src, "/files/styles/thumbnail/public/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test image with responsive style.
    $xpath = '//div[@class = "tt-image-with-responsive-style"]/picture/img[contains(@src, "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test token.
    $xpath = '//div[@class = "tt-token" and text() = "Drupal"]';
    $this->assertByXpath($xpath);

    // -- Test token with context.
    $xpath = '//div[@class = "tt-token-data" and text() = "Beta"]';
    $this->assertByXpath($xpath);

    // -- Test config.
    $xpath = '//div[@class = "tt-config" and text() = "Anonymous"]';
    $this->assertByXpath($xpath);

    // -- Test page title.
    $xpath = '//div[@class = "tt-title" and text() = "Beta"]';
    $this->assertByXpath($xpath);

    // -- Test URL.
    $url = Url::fromUserInput('/node/1', ['absolute' => TRUE])->toString();
    $xpath = sprintf('//div[@class = "tt-url"]/div[@data-case="default" and text() = "%s"]', $url);
    $this->assertByXpath($xpath);

    // -- Test URL (with langcode).
    $url = str_replace('node/1', 'ru/node/1', $url);
    $xpath = sprintf('//div[@class = "tt-url"]/div[@data-case="with-langcode" and text() = "%s"]', $url);
    $this->assertByXpath($xpath);

    // -- Test link.
    $url = Url::fromUserInput('/node/1/edit', ['absolute' => TRUE]);
    $link = Link::fromTextAndUrl('Edit', $url)->toString();
    $xpath = '//div[@class = "tt-link"]';
    self::assertEquals($link, trim($this->xpath($xpath)[0]->getHtml()));

    // -- Test link with HTML.
    $text = Markup::create('<b>Edit</b>');
    $url = Url::fromUserInput('/node/1/edit', ['absolute' => TRUE]);
    $link = Link::fromTextAndUrl($text, $url)->toString();
    $xpath = '//div[@class = "tt-link-html"]';
    self::assertEquals($link, trim($this->xpath($xpath)[0]->getHtml()));

    // -- Test status messages.
    $xpath = '//div[@class = "tt-messages"]//div[contains(@class, "messages--status") and contains(., "Hello world!")]';
    $this->assertByXpath($xpath);

    // -- Test breadcrumb.
    $xpath = '//div[@class = "tt-breadcrumb"]/nav[@class = "breadcrumb"]/ol/li/a[text() = "Home"]';
    $this->assertByXpath($xpath);

    // -- Test protected link.
    $xpath = '//div[@class = "tt-link-access"]';
    self::assertEquals('', trim($this->xpath($xpath)[0]->getHtml()));

    // -- Test token replacement.
    $xpath = '//div[@class = "tt-token-replace" and text() = "Site name: Drupal"]';
    $this->assertByXpath($xpath);

    // -- Test contextual links.
    $xpath = '//div[@class="tt-contextual-links" and not(div[@data-contextual-id])]';
    $this->assertByXpath($xpath);

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(Role::ANONYMOUS_ID);
    $this->grantPermissions($role, ['access contextual links']);
    $this->drupalGet($this->getUrl());
    $xpath = '//div[@class="tt-contextual-links" and div[@data-contextual-id]]';
    $this->assertByXpath($xpath);

    // -- Test preg replacement.
    $xpath = '//div[@class = "tt-preg-replace" and text() = "FOO-bar"]';
    $this->assertByXpath($xpath);

    // -- Test image style.
    $xpath = '//div[@class = "tt-image-style" and contains(text(), "styles/thumbnail/public/images/ocean.jpg")]';
    $this->assertByXpath($xpath);

    // -- Test transliteration.
    $xpath = '//div[@class = "tt-transliterate" and contains(text(), "Privet!")]';
    $this->assertByXpath($xpath);

    // -- Test text format.
    $xpath = '//div[@class = "tt-check-markup"]';
    self::assertEquals('<b>bold</b> strong', trim($this->xpath($xpath)[0]->getHtml()));

    // -- Format size.
    $xpath = '//div[@class = "tt-format-size"]';
    self::assertSame('12.06 KB', $this->xpath($xpath)[0]->getHtml());

    // -- Test truncation.
    $xpath = '//div[@class = "tt-truncate" and text() = "Hello…"]';
    $this->assertByXpath($xpath);

    // -- Test 'with'.
    $xpath = '//div[@class = "tt-with"]/b[text() = "Example"]';
    $this->assertByXpath($xpath);

    // -- Test nested 'with'.
    $xpath = '//div[@class = "tt-with-nested" and text() = "{alpha:{beta:{gamma:456}}}"]';
    $this->assertByXpath($xpath);

    // -- Test 'children'.
    $xpath = '//div[@class = "tt-children" and text() = "doremi"]';
    $this->assertByXpath($xpath);

    // -- Test entity view.
    $xpath = '//div[@class = "tt-node-view"]/article[contains(@class, "node--view-mode-default")]/h2[a/span[text() = "Beta"]]';
    $xpath .= '/following-sibling::div[@class = "node__content"]/div/p';
    $this->assertByXpath($xpath);

    // -- Test Field list view.
    $xpath = '//div[@class = "tt-field-list-view"]/span[contains(@class, "field--name-title") and text() = "Beta"]';
    $this->assertByXpath($xpath);

    // -- Test field item view.
    $xpath = '//div[@class = "tt-field-item-view" and text() = "Beta"]';
    $this->assertByXpath($xpath);

    // -- Test file URI from image field.
    $this->drupalGet('/node/1');
    $xpath = '//div[@class = "tt-file-uri-from-image-field" and contains(text(), "public://image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URI from a specific image field item.
    $xpath = '//div[@class = "tt-file-uri-from-image-field-delta" and contains(text(), "public://image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URI from media field.
    $xpath = '//div[@class = "tt-file-uri-from-media-field" and contains(text(), "public://image-1.png")]';
    $this->assertByXpath($xpath);

    // -- Test image style from file URI from media field.
    $xpath = '//div[@class = "tt-image-style-from-file-uri-from-media-field" and contains(text(), "styles/thumbnail/public/image-1.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URL from URI.
    $xpath = '//div[@class = "tt-file-url-from-uri" and contains(text(), "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URL from image field.
    $xpath = '//div[@class = "tt-file-url-from-image-field" and contains(text(), "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URL from a specific image field item.
    $xpath = '//div[@class = "tt-file-url-from-image-field-delta" and contains(text(), "/files/image-test.png")]';
    $this->assertByXpath($xpath);

    // -- Test file URL from media field.
    $xpath = '//div[@class = "tt-file-url-from-media-field" and contains(text(), "/files/image-1.png")]';
    $this->assertByXpath($xpath);
  }

  /**
   * Checks that an element specified by a the xpath exists on the current page.
   */
  public function assertByXpath($xpath) {
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * {@inheritdoc}
   */
  protected function initFrontPage() {
    // Intentionally empty. The parent implementation does a request to the
    // front page to init cookie. This causes some troubles in rendering
    // attached Twig template because page content type is not created at that
    // moment. We can skip this step since this test does not rely on any
    // session data.
  }

}

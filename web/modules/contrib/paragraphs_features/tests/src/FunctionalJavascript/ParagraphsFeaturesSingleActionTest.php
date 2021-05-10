<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Test display of single actions according to the dropdown_to_button setting.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesSingleActionTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Test display of action.
   */
  public function testSingleActionOption() {
    // Create content type with paragrapjs field.
    $content_type = 'test_single_action';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);

    // 1) Enable setting to show single action as button.
    $this->enableDropdownToButton();

    // Setup content type settings, this will disable all paragraph actions.
    $this->setupParagraphSettings($content_type);
    $this->drupalGet("node/add/$content_type");

    // 1a) Check that the remove action is displayed, but no dropdown toggle.
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 1b) Enable the duplicate action, it should be shown in a dropdown
    // together with the delete action.
    $this->enableDuplicateAction($content_type);

    // Check that dropdown toggle and remove action are displayed.
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 2) Disable setting to show single action as button, actions should
    // always be shown as dropdown now.
    $this->disableDropdownToButton();

    // 2a) We still have two actions enabled, dropdown should be shown anyway.
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 2b) Disable the duplicate action, it should still be shown in a dropdown.
    $this->disableDuplicateAction($content_type);
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');
  }

  /**
   * Test "add above" feature in combination with single action to button.
   */
  public function testAddAboveSupport() {
    // Create content type with paragraphs field.
    $content_type = 'test_single_action';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);

    // Enable setting to show single action as button.
    $this->enableDropdownToButton();

    // Setup content type settings, this will disable all paragraph actions.
    $this->setupParagraphSettings($content_type);

    // Check that dropdown toggle is not displayed.
    $this->drupalGet("node/add/$content_type");
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // Enable Add Above.
    $this->setParagraphFeature($content_type, 'add_above', 'add_above');

    // Check that dropdown toggle is displayed.
    $this->drupalGet("node/add/$content_type");
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');
  }

  /**
   * Helper method to set paragraphs widget feature configuration options.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   * @param string $feature
   *   Feature name.
   * @param string $value
   *   Feature value. Usually same as the feature name to enable it or "0" to
   *   disable it.
   */
  protected function setParagraphFeature($content_type, $feature, $value) {
    $this->config('core.entity_form_display.node.' . $content_type . '.default')
      ->set('content.field_paragraphs.settings.features.' . $feature, $value)
      ->save();
  }

  /**
   * Enables the duplicate action.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   */
  protected function enableDuplicateAction($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $this->setParagraphFeature($content_type, 'duplicate', 'duplicate');
    $this->drupalGet($currentUrl);
  }

  /**
   * Disables the duplicate action.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   */
  protected function disableDuplicateAction($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $this->setParagraphFeature($content_type, 'duplicate', '0');
    $this->drupalGet($currentUrl);
  }

  /**
   * Setup paragraphs field for a content type.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   */
  protected function setupParagraphSettings($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();

    // Have a default paragraph, it simplifies the clicking on the edit page.
    $this->config('core.entity_form_display.node.' . $content_type . '.default')
      ->set('content.field_paragraphs.settings.default_paragraph_type', 'test_1')
      ->set('content.field_paragraphs.third_party_settings.paragraphs_features.show_drag_and_drop', FALSE)
      ->save();

    // Disable duplicate and add_above actions.
    $this->setParagraphFeature($content_type, 'duplicate', '0');
    $this->setParagraphFeature($content_type, 'add_above', '0');

    $this->drupalGet($currentUrl);
  }

  /**
   * Enable the dropdown to button setting.
   */
  protected function enableDropdownToButton() {
    $currentUrl = $this->getSession()->getCurrentUrl();

    $this->config('paragraphs_features.settings')
      ->set('dropdown_to_button', TRUE)
      ->save();

    $this->drupalGet($currentUrl);
  }

  /**
   * Disable the dropdown to button setting.
   */
  protected function disableDropdownToButton() {
    $currentUrl = $this->getSession()->getCurrentUrl();

    $this->config('paragraphs_features.settings')
      ->set('dropdown_to_button', FALSE)
      ->save();

    $this->drupalGet($currentUrl);
  }

}

<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Tests support for extended paragraphs widgets.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesExtendedParagraphsWidgetTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddInBetweenFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of add in between feature.
    $content_type = 'test_modal_delta';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->selectFieldOption('fields[field_paragraphs][type]', 'extended_test_paragraphs');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // By default a non modal add mode should be selected.
    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "add in between" option should not be visible.');

    // Check that add in between option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "add in between" option should be available.');
    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], $this->t('Save'));

    // Check that add in between functionality is used.
    $this->drupalGet("node/add/$content_type");
    $this->assertEquals(FALSE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be hidden.');
    $this->assertEquals(TRUE, $driver->isVisible('//input[contains(@class, "paragraphs-features__add-in-between__button")]'), 'New add in between button should be visible.');

    // Add a nested paragraph and check that add in between is used only for
    // base paragraphs field, but not for the nested paragraph.
    $session->executeScript("jQuery('.paragraphs-features__add-in-between__button').trigger('click')");
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_nested")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(2, count($base_buttons), "There should be 2 add in between buttons for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($base_default_button), "There should be no default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(0, count($nested_buttons), "There should be no add in between buttons for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($nested_default_button), "There should be a default button for nested paragraph.");

    // Check first add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '0');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check last add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[last()]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '2');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check add in between button between existing paragraphs.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[3]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '2');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(5, count($base_buttons), "There should be 5 add in between buttons for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($base_default_button), "There should be no default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(0, count($nested_buttons), "There should be no add in between buttons for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($nested_default_button), "There should be a default button for nested paragraph.");
  }

}

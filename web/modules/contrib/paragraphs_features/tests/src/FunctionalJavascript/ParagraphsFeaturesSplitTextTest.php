<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Tests the paragraph text split feature.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesSplitTextTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Create new text paragraph to end of paragraphs list.
   *
   * @param int $index
   *   Index of new paragraph.
   * @param string $text
   *   Text that will be filled to text field with CKEditor.
   *
   * @return string
   *   Returns CKEditor ID.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function createNewTextParagraph($index, $text) {
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->find('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $ck_editor_id = $this->getCkEditorId($index);
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id'].insertHtml('$text');");

    return $ck_editor_id;
  }

  /**
   * Click on split text button for paragraphs text field.
   *
   * @param int $ck_editor_index
   *   Index of CKEditor field in paragraphs.
   */
  protected function clickParagraphSplitButton($ck_editor_index) {
    $this->getSession()->executeScript("jQuery('.cke_button__splittext:nth($ck_editor_index)').trigger('click');");
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Test split text feature.
   */
  public function testSplitTextFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of split text feature.
    $content_type = 'test_split_text';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->createEditor();

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // By default a non modal add mode should be selected.
    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "split text" option should not be visible.');

    // Check that split text option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "split text" option should be available.');
    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][split_text]');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], $this->t('Save'));

    // Case 1 - simple text split.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);
    // Make split of created text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // Case 2 - simple split inside word.
    $paragraph_content = '<p>Content will be split inside the word spl-it.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content);

    // Make split of created text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); var element = selection.getStartElement(); selection.selectElement(element); var ranges = selection.getRanges(); ranges[0].setStart(element.getFirst(), element.getHtml().indexOf('split') + 3); ranges[0].setEnd(element.getFirst(), element.getHtml().indexOf('split') + 3); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      '<p>Content will be spl</p>' . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
    static::assertEquals(
      '<p>it inside the word spl-it.</p>' . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // Case 3 - split text works after removal of paragraph.
    $this->drupalGet("node/add/$content_type");
    $this->createNewTextParagraph(0, '');

    // Remove the paragraph.
    $driver->executeScript("jQuery('[name=\"field_paragraphs_0_remove\"]').trigger('mousedown');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create new text paragraph.
    $ck_editor_id = $this->createNewTextParagraph(1, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(1);
    $ck_editor_id_1 = $this->getCkEditorId(2);
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // Case 4 - add of new paragraph after text split.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Set new data to both split paragraphs.
    $paragraph_content_0_new = '<p>Content that will be placed into the first paragraph after split.</p>';
    $paragraph_content_1_new = '<p>Content that will be placed into the second paragraph after split.</p>';
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $driver->executeScript("CKEDITOR.instances[\"$ck_editor_id_0\"].setData(\"$paragraph_content_0_new\");");
    $driver->executeScript("CKEDITOR.instances[\"$ck_editor_id_1\"].setData(\"$paragraph_content_1_new\");");

    // Add new text paragraph.
    $this->createNewTextParagraph(2, '');

    // Check if all texts are in the correct paragraph.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_id_2 = $this->getCkEditorId(2);
    $ck_editor_content_0 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();");
    $ck_editor_content_1 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();");
    $ck_editor_content_2 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_2'].getData();");

    static::assertEquals($paragraph_content_0_new . PHP_EOL, $ck_editor_content_0);
    static::assertEquals($paragraph_content_1_new . PHP_EOL, $ck_editor_content_1);
    static::assertEquals('', $ck_editor_content_2);

    // Case 5 - test split in middle of formatted text.
    $text = '<p>Text start</p><ol><li>line 1</li><li>line 2 with some <strong>bold text</strong> and back to normal</li><li>line 3</li></ol><p>Text end after indexed list</p>';
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $text);

    // Set selection between "bold" and "text".
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.document.findOne('strong').getChild(0)); var ranges = selection.getRanges(); var startNode = ranges[0].getBoundaryNodes().startNode; ranges[0].setStart(startNode, 4); ranges[0].setEnd(startNode, 4); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Check if all texts are correct.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_content_0 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();");
    $ck_editor_content_1 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();");

    $expected_content_0 =
      '<p>Text start</p>' . PHP_EOL . PHP_EOL .
      '<ol>' . PHP_EOL .
      "\t" . '<li>line 1</li>' . PHP_EOL .
      "\t" . '<li>line 2 with some <strong>bold</strong></li>' . PHP_EOL .
      '</ol>' . PHP_EOL;

    $expected_content_1 =
      '<ol>' . PHP_EOL .
      "\t" . '<li><strong>text</strong> and back to normal</li>' . PHP_EOL .
      "\t" . '<li>line 3</li>' . PHP_EOL .
      '</ol>' . PHP_EOL . PHP_EOL .
      '<p>Text end after indexed list</p>' . PHP_EOL;

    static::assertEquals($expected_content_0, $ck_editor_content_0);
    static::assertEquals($expected_content_1, $ck_editor_content_1);

    // Case 6 - split paragraph with multiple text fields.
    $this->addParagraphsType("test_3_text_fields");
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_1', 'text_long');
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_2', 'text_long');
    $this->addFieldtoParagraphType('test_3_text_fields', 'text_3_3', 'text_long');

    $this->drupalGet("node/add/$content_type");

    $page->find('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3_text_fields")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add required texts to text fields.
    $paragraph_content_0_text_0 = '<p>Content that will be in the first text field.</p>';
    $paragraph_content_0_text_1 = $paragraph_content_0 . $paragraph_content_1;
    $paragraph_content_0_text_2 = '<p>Content that will be in the last text field.</p>';
    $ck_editor_id_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[1]')->getAttribute('id');
    $ck_editor_id_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[2]')->getAttribute('id');
    $ck_editor_id_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[3]')->getAttribute('id');
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id_0'].insertHtml('$paragraph_content_0_text_0');");
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id_1'].insertHtml('$paragraph_content_0_text_1');");
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id_2'].insertHtml('$paragraph_content_0_text_2');");

    // Make split of created text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id_1'].getSelection(); selection.selectElement(selection.root.getChild(1)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(1);

    // Validate split results in all 6 CKEditors in 2 paragraphs.
    $ck_editor_id_para_0_text_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[1]')->getAttribute('id');
    $ck_editor_id_para_0_text_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[2]')->getAttribute('id');
    $ck_editor_id_para_0_text_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-0"]//textarea)[3]')->getAttribute('id');
    $ck_editor_id_para_1_text_0 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[1]')->getAttribute('id');
    $ck_editor_id_para_1_text_1 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[2]')->getAttribute('id');
    $ck_editor_id_para_1_text_2 = $page->find('xpath', '(//*[@data-drupal-selector="edit-field-paragraphs-1"]//textarea)[3]')->getAttribute('id');

    static::assertEquals(
      $paragraph_content_0_text_0 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_0_text_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_0_text_1'].getData();")
    );
    static::assertEquals(
      $paragraph_content_0_text_2 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_0_text_2'].getData();")
    );
    static::assertEquals(
      '',
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_1_text_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_1_text_1'].getData();")
    );
    static::assertEquals(
      '',
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_para_1_text_2'].getData();")
    );

    // Case 7 - simple text split with auto-collapse.
    // 7.1 - Enable auto-collapse.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set edit mode to closed.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][edit_mode]', 'closed');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][edit_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set auto-collapse mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][autocollapse]', 'all');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][autocollapse]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], $this->t('Save'));

    // 7.2 - Test that simple text split works with auto-collapse.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of created text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
    $this->clickParagraphSplitButton(0);

    // Validate split results. First newly created paragraph.
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // And then original collapsed paragraph.
    $page->pressButton('field_paragraphs_0_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $ck_editor_id_0 = $this->getCkEditorId(0);
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
  }

}

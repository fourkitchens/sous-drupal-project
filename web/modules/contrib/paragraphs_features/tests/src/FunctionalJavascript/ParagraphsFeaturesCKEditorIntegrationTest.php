<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\Tests\ckeditor\FunctionalJavascript\CKEditorIntegrationTest;

/**
 * Tests loading of text editors.
 *
 * @group editor
 */
class ParagraphsFeaturesCKEditorIntegrationTest extends CKEditorIntegrationTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['paragraphs_features'];

}

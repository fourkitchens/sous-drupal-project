<?php

namespace Drupal\paragraphs_features_test\Plugin\Field\FieldWidget;

use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;

/**
 * Plugin for testing of extended experimental paragraphs widget.
 *
 * @FieldWidget(
 *   id = "extended_test_paragraphs",
 *   label = @Translation("Extended Test Paragraphs EXPERIMENTAL"),
 *   description = @Translation("An extended experimental paragraphs test widget."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ExtendedTestParagraphsWidget extends ParagraphsWidget {

}

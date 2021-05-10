/**
 * CKEditor plugin for split text feature for Paragraphs text fields.
 *
 * @file plugin.js
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  // Temporal object is used to preserve data over ajax requests.
  var tmpObject = {};

  /**
   * Create new paragraph with same type after one where editor is placed.
   *
   * -------------------------------------------------------------------------*
   * Important Note:
   * This could be provided in future as option where split text could work
   * without any add mode, not just modal.
   * -------------------------------------------------------------------------*
   *
   * @param {object} editor
   *   CKEditor object.
   */

  /*
  var createNewParagraphOverDuplicate = function (editor) {
    var actionButton = $('#' + editor.name).closest('.paragraphs-subform')
      .parent()
      .find('.paragraphs-actions input[name$="_duplicate"]');

    storeTempData(editor, actionButton.attr('name'));

    actionButton.trigger('mousedown');
  };
  */

  /**
   * Create new paragraph with same type after one where editor is placed.
   *
   * @param {object} editor
   *   CKEditor object.
   */
  var createNewParagraphOverModal = function (editor) {
    var $paragraphRow = $('#' + editor.name).closest('.paragraphs-subform').closest('tr');
    var paragraphType = $paragraphRow.find('[data-paragraphs-split-text-type]').attr('data-paragraphs-split-text-type');
    var $deltaField = function ($paragraphRow) {
      var $deltaField = $paragraphRow.closest('table').siblings().find('input.paragraph-type-add-modal-delta');
      if ($deltaField.length === 0) {
        $deltaField = $paragraphRow.closest('.layer-wrapper').siblings().find('input.paragraph-type-add-modal-delta');
      }
      return $deltaField;
    }($paragraphRow);

    // Stop splitting functionality if add button is disabled or not available.
    var $addButton = $deltaField.siblings('.paragraph-type-add-modal-button');
    if ($addButton.length === 0 || $addButton.is(':disabled')) {
      return;
    }

    // New paragraph is always added after existing one - all post ajax
    // functionality expects that.
    var insertionDelta = $paragraphRow.parent().find('> tr.draggable').index($paragraphRow) + 1;
    $deltaField.val(insertionDelta);

    var paragraphTypeButtonSelector = $deltaField.attr('data-drupal-selector').substr('edit-'.length).replace(/-add-more-add-modal-form-area-add-more-delta$/, '-' + paragraphType + '-add-more').replace(/_/g, '-');
    var $actionButton = $('[data-drupal-selector^="' + paragraphTypeButtonSelector + '"]');

    // Triggering element name is required for proper handling of ajax response.
    storeTempData(editor, $actionButton.attr('name'));

    $actionButton.trigger('mousedown');
  };

  /**
   * Store temporal data required after ajax request is finished.
   *
   * @param {object} editor
   *   CKEditor object.
   * @param {string} triggerElementName
   *   Name of trigger element, required for ajax response handling.
   */
  var storeTempData = function (editor, triggerElementName) {
    var $editorObject = $('#' + editor.name);
    var selection = editor.getSelection();
    var ranges = selection.getRanges();

    // Last node that should be selected to cut content should be text type.
    var lastNode = ranges[0].document.getBody().getLast();
    ranges[0].setEndAfter(lastNode);

    // Set new selection and trigger cut for it.
    selection.selectRanges(ranges);

    // We "cut" text that will be "pasted" to new added paragraph.
    tmpObject.newContent = editor.extractSelectedHtml(true, true);
    editor.updateElement();
    editor.element.data('editor-value-is-changed', true);

    // Temporal container is used to preserve data over ajax requests.
    var $originalRow = $editorObject.closest('tr');
    tmpObject.originalRowIndex = $originalRow.parent().find('> tr.draggable').index($originalRow);
    tmpObject.originalRowParagraphId = $originalRow.closest('.field--widget-paragraphs').prop('id');
    tmpObject.originalEditorWrapperSelector = getEditorWrapperSelector(editor);

    // Triggering element is required for proper handling of ajax response.
    tmpObject.triggeringElementName = triggerElementName;

    tmpObject.split_trigger = true;
  };

  /**
   * Handler for ajax requests.
   *
   * It handles updating of editors are new paragraph is added.
   *
   * @param {object} e
   *   Event object.
   * @param {object} xhr
   *   XHR object.
   * @param {object} settings
   *   Request settings.
   */
  var onAjaxSplit = function (e, xhr, settings) {
    // Only relevant ajax actions should be handled.
    if (settings.extraData._triggering_element_name !== tmpObject.triggeringElementName || !tmpObject.split_trigger) {
      return;
    }

    var originalRow = $('#' + tmpObject.originalRowParagraphId)
      .find('table')
      .first()
      .find('> tbody > tr.draggable, > tr.draggable')[tmpObject.originalRowIndex];
    var $originalRow = $(originalRow);

    // Set "cut" data ot new paragraph.
    var $newRow = $originalRow.nextAll('tr[class*="paragraph-type--"]').first();

    // Build regex for search.
    var fieldSelector = tmpObject.originalEditorWrapperSelector.replace(/-[0-9]+-/, '-[0-9]+-');
    var $newEditor = $('[data-drupal-selector]', $newRow).filter(function () {
      return $(this).data('drupal-selector').match(fieldSelector);
    }).find('textarea');
    updateEditor($newEditor.attr('id'), tmpObject.newContent);

    // Cleanup states.
    tmpObject.split_trigger = false;

    // Delta field has to be cleaned up for proper working of add button. It
    // will not make any impact on non modal add mode.
    $originalRow.closest('table').siblings().find('input.paragraph-type-add-modal-delta').val('');
  };

  /**
   * Helper function to update content of CKEditor.
   *
   * @param {string} editorId
   *   Editor ID.
   * @param {string} data
   *   HTML as text for CKEditor.
   */
  var updateEditor = function (editorId, data) {
    if (typeof editorId === 'undefined') {
      return;
    }

    CKEDITOR.instances[editorId].setData(data, {
      callback: function () {
        this.updateElement();
        this.element.data('editor-value-is-changed', true);
      }
    });
  };

  /**
   * Makes split of paragraph text on cursor position.
   *
   * @param {object} editor
   *   CKEditor object.
   */
  var splitTextHandler = function (editor) {
    // There should be only one split request at a time.
    if (tmpObject.split_trigger) {
      return;
    }

    // After ajax response correct values should be placed in text editors.
    $(document).once('ajax-paragraph').ajaxComplete(onAjaxSplit);

    createNewParagraphOverModal(editor);
  };

  /**
   * Get wrapper Drupal selector for CKEditor.
   *
   * @param {object} editor
   *   CKEditor object.
   *
   * @return {string}
   *   Returns CKEditor wrapper ID.
   */
  var getEditorWrapperSelector = function (editor) {
    return editor.element.getAttribute('data-drupal-selector').replace(/-[0-9]+-value$/, '-wrapper');
  };

  /**
   * Verify if field is direct field of paragraph with enabled split text.
   *
   * Solution is to check that text field wrapper id direct child of subform.
   * And additionally that Wrapper ID is in list of enabled widgets.
   *
   * @param {object} editor
   *   CKEditor object.
   *
   * @return {boolean}
   *   Returns if editor is for valid paragraphs text field.
   */
  var isValidParagraphsField = function (editor) {
    // Feature not enabled,
    if (!drupalSettings.paragraphs_features || !drupalSettings.paragraphs_features.split_text) {
      return false;
    }

    var wrapperSelector = getEditorWrapperSelector(editor);
    var $subForm = $('#' + editor.name).closest('.paragraphs-subform');

    // Paragraphs split text should work only on widgets where that option is enabled.
    var paragraphWrapperId = $subForm.closest('.paragraphs-tabs-wrapper').attr('id');
    if (!drupalSettings.paragraphs_features.split_text[paragraphWrapperId]) {
      return false;
    }

    return $subForm.find('> div[data-drupal-selector="' + wrapperSelector + '"]').length === 1;
  };

  /**
   * Register define new plugin.
   */
  CKEDITOR.plugins.add('splittext', {
    icons: 'splittext',
    hidpi: true,
    requires: '',

    init: function (editor) {

      // Split Text functionality should be added only for paragraphs Text fields.
      if (!isValidParagraphsField(editor)) {
        return;
      }

      // Get module path necessary for button icons.
      var modulePath = drupalSettings.paragraphs_features.split_text._path;

      editor.addCommand('splitText', {
        exec: function (editor) {
          splitTextHandler(editor, 'before');
        }
      });

      editor.ui.addButton('SplitText', {
        label: 'Split Text',
        icon: '/' + modulePath + '/js/plugins/splittext/icons/splittext.png',
        command: 'splitText'
      });

      if (editor.addMenuItems) {
        editor.addMenuGroup('splittext');
        editor.addMenuItems({
          splittext: {
            label: Drupal.t('Split Text'),
            command: 'splitText',
            icon: '/' + modulePath + '/js/plugins/splittext/icons/splittext.png',
            group: 'splittext',
            order: 1
          }
        });
      }

      if (editor.contextMenu) {
        editor.contextMenu.addListener(function () {
          return {
            splittext: CKEDITOR.TRISTATE_OFF
          };
        });
      }
    }
  });

}(jQuery, Drupal, drupalSettings, CKEDITOR));

/**
 * @file thunder-paragraph-features.add-in-between.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Ensure namespace for paragraphs features exists.
   */
  if (typeof Drupal.paragraphs_features === 'undefined') {
    Drupal.paragraphs_features = {};
  }

  /**
   * Namespace for add in between paragraphs feature.
   *
   * @type {Object}
   */
  Drupal.paragraphs_features.add_in_between = {};

  /**
   * Define add in between row template.
   *
   * @param {object} config
   *   Configuration options for add in between row template.
   *
   * @return {string}
   *   Returns markup string for add in between row.
   */
  Drupal.theme.paragraphsFeaturesAddInBetweenRow = function (config) {
    return '' +
      '<tr class="paragraphs-features__add-in-between__row">' +
      '  <td colspan="100%">' +
      '    <div class="paragraphs-features__add-in-between__wrapper">' +
      '      <input class="paragraphs-features__add-in-between__button button--small js-show button js-form-submit form-submit" type="submit" value="' + config.text + '">' +
      '    </div>' +
      '  </td>' +
      '</tr>';
  };

  /**
   * Init add in between buttons for paragraphs table.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenInit = {
    attach: function () {
      $.each(drupalSettings.paragraphs_features.add_in_between, function (paragraphsWidgetId) {
        Drupal.paragraphs_features.add_in_between.initParagraphsWidget(paragraphsWidgetId);
      });
    }
  };

  /**
   * Get paragraphs add modal block in various themes structures.
   *
   *  gin:
   *   .layer-wrapper table
   *   .form-actions
   * claro:
   *   table
   *   .form-actions
   * thunder-admin / seven:
   *   table
   *   .clearfix
   *
   * @param {Object} $table
   *   jQuery object for the table element.
   *
   * @return {Object} $addModalBlock
   *   jQuery object for the add modal block element.
   */
  Drupal.paragraphs_features.add_in_between.getAddModalBlock = function ($table) {
    var $addModalBlock = $table.siblings('.form-actions, .clearfix');
    if ($addModalBlock.length === 0) {
      $addModalBlock = $table.parents('.layer-wrapper').first().siblings('.form-actions');
    }
    return $addModalBlock;
  };

  /**
   * Init paragraphs widget with add in between functionality.
   *
   * @param {string} paragraphsWidgetId
   *   Paragraphs Widget ID.
   */
  Drupal.paragraphs_features.add_in_between.initParagraphsWidget = function (paragraphsWidgetId) {
    var $tables = $('#' + paragraphsWidgetId).find('.field-multiple-table').first()
      .once('paragraphs-features-add-in-between-init');

    $tables.each(function (index, table) {
      var $table = $(table);
      var $addModalBlock = Drupal.paragraphs_features.add_in_between.getAddModalBlock($table);
      var $addModalButton = $addModalBlock.find('.paragraph-type-add-modal-button');

      // Ensure that paragraph list uses modal dialog.
      if ($addModalButton.length === 0) {
        return;
      }

      // A new button for adding at the end of the list is used.
      $addModalBlock.hide();

      var rowMarkup = Drupal.theme('paragraphsFeaturesAddInBetweenRow', {text: Drupal.t('+ Add')});

      // Add buttons and adjust drag-drop functionality.
      var $tableBody = $table.find('> tbody');
      $tableBody.find('> tr').each(function (index, rowElement) {
        $(rowMarkup).insertBefore(rowElement);
      });

      // Add a new button for adding a new paragraph to the end of the list.
      if ($tableBody.length === 0) {
        $table.append('<tbody></tbody>');

        $tableBody = $table.find('> tbody');
      }
      $tableBody.append(rowMarkup);

      // Adding of a new paragraph can be disabled for some reason.
      if ($addModalButton.is(':disabled')) {
        $tableBody.find('.paragraphs-features__add-in-between__button')
          .prop('disabled', 'disabled').addClass('is-disabled');
      }

      // Trigger attaching of behaviours for added buttons.
      Drupal.behaviors.paragraphsFeaturesAddInBetweenRegister.attach($table);
    });
  };

  /**
   * Click handler for click "Add" button between paragraphs.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenRegister = {
    attach: function (context) {
      $('.paragraphs-features__add-in-between__button', context)
        .once('paragraphs-features-add-in-between')
        .on('click', function (event) {
          var $button = $(this);
          var $add_more_wrapper = Drupal.paragraphs_features.add_in_between.getAddModalBlock($button.closest('table'))
            .find('.paragraphs-add-dialog');
          var delta = $button.closest('tr').index() / 2;

          // Set delta before opening of dialog.
          Drupal.paragraphs_features.add_in_between.setDelta($add_more_wrapper, delta);
          Drupal.paragraphsAddModal.openDialog($add_more_wrapper, Drupal.t('Add In Between'));

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();
        });
    }
  };

  /**
   * Set delta into hidden field, where a new paragraph will be added.
   *
   * @param {Object} $add_more_wrapper
   *   jQuery object for add more wrapper element.
   * @param {int} delta
   *   Integer value for delta position where a new paragraph should be added.
   */
  Drupal.paragraphs_features.add_in_between.setDelta = function ($add_more_wrapper, delta) {
    var $delta = $add_more_wrapper.parents('.form-actions, .clearfix')
      .find('.paragraph-type-add-modal-delta');

    $delta.val(delta);
  };

  /**
   * Init Drag-Drop handling for add in between buttons for paragraphs table.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenTableDragDrop = {
    attach: function () {
      for (var tableId in drupalSettings.tableDrag) {
        if (Object.prototype.hasOwnProperty.call(drupalSettings.tableDrag, tableId)) {
          Drupal.paragraphs_features.add_in_between.adjustDragDrop(tableId);

          jQuery('#' + tableId)
            .once('in-between-buttons-columnschange')
            .on('columnschange', function () {
              var tableId = $(this).prop('id');

              Drupal.paragraphs_features.add_in_between.adjustDragDrop(tableId);
            });
        }
      }
    }
  };

  /**
   * Adjust drag-drop functionality for paragraphs with "add in between"
   * buttons.
   *
   * @param {string} tableId
   *   Table ID for paragraphs table with adjusted drag-drop behaviour.
   */
  Drupal.paragraphs_features.add_in_between.adjustDragDrop = function (tableId) {
    // Ensure that function changes are executed only once.
    if (!Drupal.tableDrag[tableId] || Drupal.tableDrag[tableId].paragraphsDragDrop) {
      return;
    }
    Drupal.tableDrag[tableId].paragraphsDragDrop = true;

    // Helper function to create sequence execution of two bool functions.
    var sequenceBoolFunctions = function (originalFn, newFn) {
      return function () {
        var result = originalFn.apply(this, arguments);

        if (result) {
          result = newFn.apply(this, arguments);
        }

        return result;
      };
    };

    // Allow row swap if it's not in between button.
    var paragraphsIsValidSwap = function (row) {
      return !$(row).hasClass('paragraphs-features__add-in-between__row');
    };

    // Sequence default .isValidSwap() function with custom paragraphs function.
    var rowObject = Drupal.tableDrag[tableId].row;
    rowObject.prototype.isValidSwap = sequenceBoolFunctions(rowObject.prototype.isValidSwap, paragraphsIsValidSwap);

    // provide custom .onSwap() handler to reorder "Add" buttons.
    rowObject.prototype.onSwap = function (row) {
      var $table = $(row).closest('table');
      var allDrags = $table.find('> tbody > tr.draggable');
      var allAdds = $table.find('> tbody > tr.paragraphs-features__add-in-between__row');

      // We have to re-stripe add in between rows.
      allDrags.each(function (index, dragElem) {
        if (allAdds[index]) {
          $(dragElem).before(allAdds[index]);
        }
      });
    };
  };

}(jQuery, Drupal, drupalSettings));

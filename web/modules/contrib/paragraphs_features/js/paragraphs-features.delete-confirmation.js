/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal) {

  'use strict';

  // Ensure namespace.
  Drupal.paragraphs_features = Drupal.paragraphs_features || {};

  /**
   * Theme function for remove button
   *
   * @param {object} options
   *   Options for delete confirmation button.
   *
   * @return {string}
   *   Returns markup.
   */
  Drupal.theme.paragraphsFeaturesDeleteConfirmationButton = function (options) {
    return '<button type="button" class="paragraphs-features__delete-confirm ' + options.class + '">' + Drupal.t('Remove') + '</button>';
  };

  /**
   * Theme functions for confirmation message.
   *
   * @param {object} options
   *   Configuration options used to construct the markup.
   * @return {string}
   *   Returns markup.
   */
  Drupal.theme.paragraphsFeaturesDeleteConfirmationMessage = function (options) {
    return '' +
      '<div class="paragraphs-features__delete-confirmation">' +
      '  <div class="paragraphs-features__delete-confirmation__message">' + options.message + '</div>' +
      '  <div class="form-actions js-form-wrapper form-wrapper" id="edit-actions">' +
      '    <button type="button" class="paragraphs-features__delete-confirmation__remove-button button button--primary js-form-submit form-submit">' + options.remove + '</button>' +
      '    <button type="button" class="paragraphs-features__delete-confirmation__cancel-button button js-form-submit form-submit">' + options.cancel + '</button>' +
      '  </div>' +
      '</div>';
  };

  /**
   * Handler for paragraphs_actions custom remove button.
   * Also adds Confirmation message, buttons and their handlers.
   *
   * @return {Function}
   *   Returns event handler.
   */
  Drupal.paragraphs_features.deleteConfirmHandler = function () {
    return function (event) {
      var $wrapper = $(event.target).parents('div[id*="-item-wrapper"]').first();
      // Hide children.
      $wrapper.children().toggleClass('visually-hidden');
      // Add markup.
      $wrapper.append(Drupal.theme('paragraphsFeaturesDeleteConfirmationMessage', {message: Drupal.t('Are you sure you want to remove this paragraph?'), remove: Drupal.t('Remove'), cancel: Drupal.t('Cancel')}));
      // Add handlers for buttons.
      $wrapper.find('.paragraphs-features__delete-confirmation__cancel-button').bind('mousedown', Drupal.paragraphs_features.deleteConfirmRemoveHandler());
      $wrapper.find('.paragraphs-features__delete-confirmation__remove-button').bind('mousedown', Drupal.paragraphs_features.deleteConfirmCancelHandler());
    };
  };

  /**
   * Handler for remove action.
   *
   * @param {Event} event
   *   An event
   * @return {Function}
   *   Returns event handler.
   */
  Drupal.paragraphs_features.deleteConfirmCancelHandler = function () {
    return function (event) {
      $(event.target).parents('div[id*="-item-wrapper"]').first().find('.paragraphs-actions *[data-drupal-selector*="-remove"]').trigger('mousedown');
    };
  };

  /**
   * Handler for cancel action.
   *
   * @param {Event} event
   *   An event
   * @return {Function}
   *   Returns event handler.
   */
  Drupal.paragraphs_features.deleteConfirmRemoveHandler = function () {
    return function (event) {
      var $wrapper = $(event.target).parents('div[id*="-item-wrapper"]').first();
      $wrapper.children('.paragraphs-features__delete-confirmation').first().remove();
      $wrapper.children().toggleClass('visually-hidden');
    };
  };

  /**
   * Init inline remove confirmation form.
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.paragraphsFeaturesDeleteConfirmationInit = {
    attach: function (context, settings) {
      var $actions = $(context).find('.paragraphs-actions').once('paragraphs-features-delete-confirmation-init');
      $actions.find('*[data-drupal-selector*="remove"]').each(function () {
        // Add custom button element and handler.
        var $element = $(Drupal.theme('paragraphsFeaturesDeleteConfirmationButton', {class: $(this).attr('class')})).insertBefore(this);
        $element.bind('mousedown', Drupal.paragraphs_features.deleteConfirmHandler());
        // Propagate disabled attribute.
        if ($(this).is(':disabled')) {
          $element.prop('disabled', 'disabled').addClass('is-disabled');
        }
        // Hide original Button
        $(this).wrap('<div class="visually-hidden"></div>');
      });
    }
  };
}(jQuery, Drupal));

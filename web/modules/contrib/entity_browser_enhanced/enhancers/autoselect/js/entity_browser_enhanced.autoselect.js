/**
 * @file
 * Behaviors for the Entity Browser Autoselect.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.entityBrowserAutoselect = {
    attach: function (context, settings) {

      $('.views-field-entity-browser-select', context).each(function () {
        var $browser_select = $(this);

        if (drupalSettings.entity_browser_widget.auto_select) {
          $browser_select.once('register-row-click').click(function (event) {
            event.preventDefault();

              var $input = $browser_select.find('input.form-checkbox');

              $browser_select.parents('form')
                .find('.entities-list')
                .trigger('add-entities', [[$input.val()]]);
          });
        }
      });

    }
  };

})(jQuery, Drupal, drupalSettings);

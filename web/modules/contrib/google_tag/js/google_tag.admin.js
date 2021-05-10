/**
 * @file
 * Behaviors and utility functions for administrative pages.
 */

(function ($) {

  "use strict";

  /**
  * Provides summary information for the vertical tabs.
  */
  Drupal.behaviors.gtmInsertionSettings = {
    attach: function (context, settings) {

      // Pass context parameters to outer function.
      function toggleValuesSummary(element, plural, adjective) {
        // Return a callback function as expected by drupalSetSummary().
        return function (context) {
          console.log("inside toggleValuesSummary");
          console.log("plural=" + plural);
          var str = '';
          var toggle = $('input[type="radio"]:checked', context).val();
          var values;
          if (element == 'checkbox') {
            values = $('input[type="checkbox"]:checked + label', context).length;
          }
          else {
            var values = $('textarea', context).val();
          }
          if (toggle == 'exclude listed') {
            if (!values) {
              str = 'All !plural';
            }
            else {
              str = 'All !plural except !adjective !plural';
            }
          }
          else {
            if (!values) {
              str = 'No !plural';
            }
            else {
              str = 'Only !adjective !plural';
            }
          }
          const args = {'!plural': plural, '!adjective': adjective};
          return Drupal.t(Drupal.formatString(str, args));
        }
      }

      // @todo Magic to use 'data-drupal-selector' vs. 'details#edit-path'?
      var element, plural, adjective;

      element = 'checkbox';
      adjective = 'selected';
      var selectors = ['role', 'gtag-domain', 'gtag-language'];
      for (const selector of selectors) {
        plural = selector.replace('gtag-', '') + 's';
        $('[data-drupal-selector="edit-' + selector + '"]', context).drupalSetSummary(toggleValuesSummary(element, plural, adjective));
      }

      element = 'textarea';
      adjective = 'listed';
      selectors = ['path', 'status'];
      for (const selector of selectors) {
        plural = selector.replace('gtag-', '').replace('status', 'statuse') + 's';
        $('[data-drupal-selector="edit-' + selector + '"]', context).drupalSetSummary(toggleValuesSummary(element, plural, adjective));
      }
    }
  };

})(jQuery);

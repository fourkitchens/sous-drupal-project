/**
 * @file content_browser.view.js
 */
(function ($, Drupal) {

  "use strict";

  /**
   * Registers behaviours related to view widget.
   */
  Drupal.behaviors.ContentBrowserView = {
    attach: function (context) {
      $('body').addClass('content-browser-page').fadeIn();

      $('.views-row').once('bind-click-event').click(function  () {
        var input = $(this).find('.views-field-entity-browser-select input');
        input.prop('checked', !input.prop('checked'));
        if (input.prop('checked')) {
          $(this).addClass('checked');
        }
        else {
          $(this).removeClass('checked');
        }
      });

      var $view = $('.view-content');
      // Save the scroll position.
      var scroll = document.body.scrollTop;
      // Remove old Masonry object if it exists. This allows modules like
      // Views Infinite Scroll to function with File Browser.
      if ($view.data('masonry')) {
        $view.masonry('destroy');
      }
      $view.masonry({
        itemSelector: '.views-row',
        columnWidth: 350,
        gutter: 15
      });
      // Jump to the old scroll position.
      document.body.scrollTop = scroll;
    }
  };

}(jQuery, Drupal));

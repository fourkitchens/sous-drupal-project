/**
 * @file file_browser.view.js
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Renders the file counter based on our internally tracked count.
   */
  function renderFileCounter () {
    $('.file-browser-file-counter').each(function () {
      $(this).remove();
    });
    var counter = [];
    $('.entities-list [data-entity-id]').each(function () {
      if (counter[this.dataset.entityId]) {
        ++counter[this.dataset.entityId];
      }
      else {
        counter[this.dataset.entityId] = 1;
      }
    });
    for (var id in counter) {
      var count = counter[id];
      if (count > 0) {
        var text = Drupal.formatPlural(count, 'Selected one time', 'Selected @count times');
        var $counter = $('<div class="file-browser-file-counter"></div>').text(text);
        $('[name="entity_browser_select[file:' + id + ']"]').closest('.grid-item').find('.grid-item-info').prepend($counter);
      }
    }
  }

  /**
   * Adjusts the padding on the body to account for the fixed actions bar.
   */
  function adjustBodyPadding () {
    setTimeout(function () {
      $('body').css('padding-bottom', $('.file-browser-actions').outerHeight() + 'px');
    }, 2000);
  }

  /**
   * Initializes Masonry for the view widget.
   */
  Drupal.behaviors.fileBrowserMasonry = {
    attach: function (context) {
      var $item = $('.grid-item', context);
      var $view = $item.parent().once('file-browser-init');
      if ($view.length) {
        $view.prepend('<div class="grid-sizer"></div><div class="gutter-sizer"></div>');

        // Indicate that images are loading.
        $view.append('<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>');
        $view.imagesLoaded(function () {
          // Save the scroll position.
          var scroll = document.body.scrollTop;
          // Remove old Masonry object if it exists. This allows modules like
          // Views Infinite Scroll to function with File Browser.
          if ($view.data('masonry')) {
            $view.masonry('destroy');
          }
          $view.masonry({
            columnWidth: '.grid-sizer',
            gutter: '.gutter-sizer',
            itemSelector: '.grid-item',
            percentPosition: true,
            isFitWidth: true
          });
          // Jump to the old scroll position.
          document.body.scrollTop = scroll;
          // Add a class to reveal the loaded images, which avoids FOUC.
          $item.addClass('item-style');
          $view.find('.ajax-progress').remove();
        });
      }
    }
  };

  /**
   * Checks the hidden Entity Browser checkbox when an item is clicked.
   *
   * This behavior provides backwards-compatibility for users not using
   * auto-select and multi-step.
   */
  Drupal.behaviors.fileBrowserClickProxy = {
    attach: function (context, settings) {
      if (!settings.entity_browser_widget.auto_select) {
        $('.grid-item', context).once('bind-click-event').click(function () {
          var input = $(this).find('.views-field-entity-browser-select input');
          input.prop('checked', !input.prop('checked'));
          if (input.prop('checked')) {
            $(this).addClass('checked');
          }
          else {
            $(this).removeClass('checked');
          }
        });
      }
    }
  };

  /**
   * Tracks when entities have been added or removed in the multi-step form,
   * and displays that information on each grid item.
   */
  Drupal.behaviors.fileBrowserEntityCount = {
    attach: function (context) {
      adjustBodyPadding();
      renderFileCounter();
      // Indicate when files have been selected.
      var $entities = $('.entities-list', context).once('file-browser-add-count');
      if ($entities.length) {
        $entities.bind('add-entities', function (event, entity_ids) {
          adjustBodyPadding();
          renderFileCounter();
        });

        $entities.bind('remove-entities', function (event, entity_ids) {
          adjustBodyPadding();
          renderFileCounter();
        });
      }
    }
  };

}(jQuery, Drupal));

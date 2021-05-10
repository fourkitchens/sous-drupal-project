/**
 * @file
 * Provides Colorbox integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches slick behavior to HTML element identified by .slick--colorbox.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickColorbox = {
    attach: function (context) {
      var me = Drupal.slickColorbox;

      $(context).on('cbox_open', function () {
        me.set('slickPause');
      });

      $(context).on('cbox_load', function () {
        me.set('setPosition');
      });

      $(context).on('cbox_closed', function () {
        me.set('slickPlay');
      });

      $(context).on('cbox_complete', function () {
        me.set('complete');
      });

      $('.slick--colorbox', context).once('slick-colorbox').each(doSlickColorbox);
    }
  };

  /**
   * Adds each slide a reliable ordinal to get correct current with clones.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The slick HTML element.
   */
  function doSlickColorbox(i, elm) {
    $('.slick__slide', elm).each(function (j, el) {
      $(el).attr('data-delta', j);
    });
  }

  /**
   * Slick Colorbox utility functions.
   *
   * @namespace
   */
  Drupal.slickColorbox = Drupal.slickColorbox || {

    /**
     * Sets method related to Slick methods.
     *
     * @name set
     *
     * @param {string} method
     *   The method to apply to .slick__slider element.
     */
    set: function (method) {
      var $box = $.colorbox.element();
      var $slider = $box.closest('.slick__slider');
      var $wrap = $slider.closest('.slick-wrapper');
      var $clonedBox = $slider.find('.slick-cloned .litebox');
      var total = $slider.find('.slick__slide:not(.slick-cloned) .litebox').length;
      var $counter = $('#cboxCurrent');
      var curr;

      if ($slider.length) {
        // Cannot use dataSlickIndex which maybe negative with slick clones.
        curr = Math.abs($box.closest('.slick__slide').data('delta'));
        if (isNaN(curr)) {
          curr = 0;
        }

        // Slick is broken after colorbox close, do setPosition manually.
        if (method === 'setPosition') {
          if ($clonedBox.length) {
            $clonedBox.removeClass('cboxElement');
          }

          if ($wrap.length) {
            var $thumb = $wrap.find('.slick--thumbnail .slick__slider');
            $thumb.slick('slickGoTo', curr);
          }
          $slider.slick('slickGoTo', curr);
        }
        else if (method === 'slickPlay') {
          var slick = $slider.slick('getSlick');
          if (slick && slick.options.autoPlay) {
            $slider.slick(method);
          }

          // Fixes Firefox, IE width recalculation after closing the colorbox.
          $slider.slick('refresh');

          // Re-attaches behaviors.
          if ($clonedBox.length) {
            $clonedBox.addClass('cboxElement');

            $clonedBox.each(function (i, box) {
              Drupal.attachBehaviors(box);
            });
          }
        }
        else if (method === 'complete') {
          // Actually only needed at first launch, but no first launch event.
          if ($counter.length) {
            var current = drupalSettings.colorbox.current || false;
            if (current) {
              current = current.replace('{current}', (curr + 1)).replace('{total}', total);
            }
            else {
              current = Drupal.t('@curr of @total', {'@curr': (curr + 1), '@total': total});
            }
            $counter.text(current);
          }
        }
        else {
          $slider.slick(method);
        }
      }
    }
  };

}(jQuery, Drupal));

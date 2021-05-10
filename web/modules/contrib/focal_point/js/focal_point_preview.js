/**
 * @file
 * Javascript functionality for the focal point preview page.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Focal Point preview.
   */
  Drupal.behaviors.focalPointPreview = {
    attach: function (context, settings) {
      var $focalPointDerivativePreviews = $(".focal-point-derivative-preview");

      var $focalPointImagePreview = $("#focal-point-preview-image");
      var $focalPointImagePreviewLabel = $("#focal-point-preview-label");

      var originalImageURL = $focalPointImagePreview.attr('src');
      var originalImagePreviewLabel = $focalPointImagePreviewLabel.html();

      // Add a click event to each derivative preview.
      $focalPointDerivativePreviews.each(function () {
        $(this).click(function (event) {
          // Remove any image style classes added by active previews.
          $(".focal-point-derivative-preview.active").each(function () {
            $focalPointImagePreview.removeClass($(this).data('image-style'))
          });
          // Before adding the active class, remove the active class from all
          // derivative previews in case one is already active.
          $(".focal-point-derivative-preview").removeClass('active');
          var $this = $(this);
          var image_style = $this.data('image-style');
          $this.addClass('active');

          // Set the main preview label and image to this derivative since it
          // was just clicked.
          var imageSrc = $this.find("img").attr('src');
          var imageLabel = $this.find('h3').html();
          $focalPointImagePreviewLabel.html(imageLabel);
          $focalPointImagePreview.attr('src', imageSrc);
          $focalPointImagePreview.addClass(image_style);
          $focalPointImagePreview.data('image-style', image_style);

          // Prevent the window click event from running.
          event.stopPropagation();
        });
      });

      // Add some window events for reverting to the original image.
      $(window).click(function (event) {
        resetPreview();
      });
      $(window).keyup(function (event) {
        // Check if the esc key was pressed.
        if (event.keyCode === 27) {
          resetPreview();
        }
      });

      /**
       * Reset the main preview image.
       *
       * Remove the active class from all derivative image previews and then
       * reset the main preview image and label.
       */
      function resetPreview() {
        $focalPointDerivativePreviews.removeClass('active');
        $focalPointImagePreviewLabel.html(originalImagePreviewLabel);
        $focalPointImagePreview.removeClass($focalPointImagePreview.data('image-style'));
        $focalPointImagePreview.attr('src', originalImageURL);
      }

    }

  };
})(jQuery, Drupal);

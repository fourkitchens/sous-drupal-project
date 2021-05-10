(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.TaxonomyManagerTermData = function (tid, tree) {
    // We change the hidden form element which then triggers the AJAX system.
    $('input[name=load-term-data]').val(tid).trigger('change');
  };

})(jQuery, Drupal, drupalSettings);

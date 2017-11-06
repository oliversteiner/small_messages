(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.smmgMainBehavior = {
    attach: function (context, settings) {

      console.log('smmg');
      test();

// Auto Send Filter
      $('*[id^=edit-empfaenger-gruppe]').change(function () {
        updateView();
      });

      $('*[id^=edit-goenner-typ]').change(function () {
        updateView();
      });

      $('*[id^=edit-goenner-status]').change(function () {
        updateView();
      });

      $('*[id^=edit-field-newsletter-mailto-groups]').change(function () {
        updateView();
      });

      $('*[id^=edit-field-gruppe-target-id]').change(function () {
        updateView();
      });

      $('*[id^=edit-items-per-page]').change(function () {
        updateView();
      });

      // Editor status element ausblenden
      $('*[id^="edit-meta"]').parent().parent().hide();

    }
  };

  /**
   *
   *
   */
  function test() {
    console.log('smmg - test');


  }


  function updateView(){
    // Bootstrap Theme
    var $submit_button = $('*[id^=edit-submit-smmg]');

    $submit_button.trigger('click');
   // $submit_button.click();


    $('.smmg-views .view-content').fadeTo('medium', 0);


  }


})(jQuery, Drupal, drupalSettings);
(function($, Drupal, drupalSettings) {
  Drupal.behaviors.smmgMainBehavior = {


    attach(context, settings) {

      $("#smmg-main", context).once('smmgMainBehavior')
        .each(() => {

        });
    },
  };
})(jQuery, Drupal, drupalSettings);

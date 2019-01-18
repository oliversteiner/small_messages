(function($, Drupal, drupalSettings) {
  Drupal.behaviors.smmgMainBehavior = {
    updateView() {
      // Bootstrap Theme
      const $submitButton = $('*[id^=edit-submit-smmg]');

      $submitButton.trigger('click');
      // $submit_button.click();

      $('.smmg-views .view-content').fadeTo('medium', 0);
    },

    attach(context, settings) {
      console.log('smmg');

      $(context).once('smmgMainBehavior')
        .each(() => {
          console.log('smmg2');

          const $messages = $("a:contains('Messages')");

          console.log('$messages', $messages);

        });

      /*
        // Auto Send Filter
            $('*[id^=edit-empfaenger-gruppe]').change(() => {
              this.updateView();
            });

            $('*[id^=edit-goenner-typ]').change(() => {
              this.updateView();
            });

            $('*[id^=edit-goenner-status]').change(() => {
              this.updateView();
            });

            $('*[id^=edit-field-newsletter-mailto-groups]').change(() => {
              this.updateView();
            });

            $('*[id^=edit-field-gruppe-target-id]').change(() => {
              this.updateView();
            });

            $('*[id^=edit-items-per-page]').change(() => {
              this.updateView();
            });

            // Editor status element ausblenden
            $('*[id^="edit-meta"]')
              .parent()
              .parent()
              .hide();
              */
    },
  };
})(jQuery, Drupal, drupalSettings);

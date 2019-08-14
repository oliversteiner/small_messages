(function($, Drupal, drupalSettings) {
  Drupal.behaviors.smmgJSONBehavior = {


    attach: function(context, settings) {

      $('main', context).once('smmgJSONBehavior')
        .each(() => {

          console.log('smmgJSONBehavior');


          $('.smmg-json-data-toggle').click((elem) => {
            console.log('click', elem);
            console.log('elem', elem.currentTarget);
            console.log('data-id', elem.currentTarget.dataset.id);
            const $elem_is_open = $('span.is_open', elem.currentTarget);
            const $elem_is_closed = $('span.is_closed', elem.currentTarget);

            $elem_is_open.toggle();
            $elem_is_closed.toggle();

            const id = elem.currentTarget.dataset.id;

            $('.' + id).toggle();
          });
        });
    },
  };
})(jQuery, Drupal, drupalSettings);

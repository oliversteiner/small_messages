(function($, Drupal, drupalSettings) {
  'use strict';

  /**
   *
   */
  Drupal.behaviors.smmgInquiryBehavior = {
    attach: function(context, settings) {
      $('#smmg-inquiry', context)
        .once('smmgInquiryBehavior')

        .each(() => {
          console.log('smmg-inquiry');

          const list = drupalSettings.subscribers;

          toggleMessageDetails();
          toggleMessageHTML();
          toggleSubscriberList();
          removeFromList(list);
        });
    },
  };

  /**
   *
   * toggleMessageDetails
   *
   */
  function toggleMessageDetails() {
    $('.smmg-body-toggle-trigger').click(function() {
      var $target = $('.smmg-body');
      var $trigger = $('.smmg-body-toggle-trigger');
      var css_class = 'smmg-body-komplete';

      if ($target.hasClass(css_class)) {
        $target.removeClass(css_class);
        $trigger.html('komplette Nachricht anzeigen');
      } else {
        $target.addClass(css_class);
        $trigger.html('Details ausblenden');
      }
    });
  }

  /**
   *
   * toggleMessageHTML
   *
   */
  function toggleMessageHTML() {
    $('.smmg-body-html-toggle-trigger').click(function() {
      $('.smmg-body-html').toggle();
    });
  }

  /**
   *
   * toggleSubscriberList
   *
   */
  function toggleSubscriberList() {
    var $trigger = $('.smmg-subscriber-list-trigger');

    $trigger.click(function() {
      var $description = $('.smmg-subscriber-list-description');
      var css_class = 'active';
      var group_id = this.dataset.subscriberGroupId;

      if (group_id == 0) {
        var $target = $('*[class^=smmg-subscriber-list-block-]');

        if ($target.hasClass(css_class)) {
          $target.removeClass(css_class);
          $target.hide();
          $description.html('Alle Empfänger auflisten');
        } else {
          $target.addClass(css_class);
          $target.show();
          $description.html('Alle Empfänger ausblenden');
        }
      } else {
        var $target = $('.smmg-subscriber-list-block-' + group_id);

        if ($target.hasClass(css_class)) {
          $target.removeClass(css_class);
          $target.hide();
        } else {
          $target.addClass(css_class);
          $target.show();
        }
      }
    });
  }

  /**
   *
   * toggleSubscriberList
   *
   */
  function removeFromList(list) {
    const $trigger = $('.smmg-subscriber-list-remove');

    $trigger.click(function(e) {
      // get Data
      const subscriber_group_id = this.dataset.subscriberGroupId;
      const subscriber_group_name =
        drupalSettings.subscribers[subscriber_group_id].title;

      const subscriber_id = this.dataset.subscriberId;
      const subscriber_name =
        drupalSettings.subscribers[subscriber_group_id]['members'][
          subscriber_id
        ];

      $('.ajax-container').html(
        '<strong>' +
          subscriber_name +
          '</strong> von der Liste <strong>' +
          subscriber_group_name +
          '</strong> entfernen...',
      );

      $.ajax({
        url: Drupal.url(
          'smmg/ajax/toggle_subscriber_group/' +
            subscriber_id +
            '/' +
            subscriber_group_id,
        ),
        type: 'POST',
        data: {},
        dataType: 'json',
        success: function(results) {
          var message = results[0];

          if (message.method === 'removeClass') {
            $('.ajax-container')
              .addClass('active')
              .html(
                '<strong>' +
                  subscriber_name +
                  '</strong> wurde aus der Gruppe <strong>' +
                  subscriber_group_name +
                  '</strong> entfernt',
              );
            $('.smmg-subscriber-member-' + subscriber_id).hide();
          } else {
            $(message.selector).html(message.data);
          }
          return results;
        },
      });
    });
  }
})(jQuery, Drupal, drupalSettings);

(function ($, Drupal, drupalSettings) {

    'use strict';

    /**
     *
     */
    Drupal.behaviors.smmgInquiryBehavior = {
        attach: function (context, settings) {

            console.log('smmg -inquiry');
            console.log(drupalSettings.subscribers);

            var list = drupalSettings.subscribers;

            toggleMessageDetails();
            toggleSubscriberList();
            removeFromList(list);

            /* plaintext */
            $('.smmg-body-plaintext-toggle').click(function () {
                $('#smmg-body-plaintext').toggle();
            });

            /* htmltext */
            $('.smmg-body-htmltext-toggle').click(function () {
                $('#smmg-body-htmltext').toggle();
            });
        }

    };

    /**
     *
     * toggleMessageDetails
     *
     */
    function toggleMessageDetails() {
        $('.smmg-body-toggle-trigger').click(function () {

            var $target = $('.smmg-body');
            var $trigger = $('.smmg-body-toggle-trigger');
            var css_class = "smmg-body-komplete";

            if ($target.hasClass(css_class)) {

                $target.removeClass(css_class);
                $trigger.html('komplette Nachricht anzeigen');


            }
            else {
                $target.addClass(css_class);
                $trigger.html('Details ausblenden');

            }


        })
    }

    /**
     *
     * toggleSubscriberList
     *
     */
    function toggleSubscriberList() {


        var $trigger = $('.smmg-subscriber-list-trigger');

        $trigger.click(function () {

            console.log('toggleSubscriberList');


            var $description = $('.smmg-subscriber-list-description');
            var css_class = "active";
            var group_id = this.dataset.subscriberTagId;

            console.log(this.dataset);
            console.log(group_id);
            if (group_id == 0) {

                var $target = $('*[class^=smmg-subscriber-list-block-]');

                if ($target.hasClass(css_class)) {

                    $target.removeClass(css_class);
                    $target.hide();
                    $description.html('Alle Empfänger auflisten');
                }
                else {
                    $target.addClass(css_class);
                    $target.show();
                    $description.html('Alle Empfänger ausblenden');
                }
            }
            else {

                var $target = $('.smmg-subscriber-list-block-' + group_id);

                if ($target.hasClass(css_class)) {

                    $target.removeClass(css_class);
                    $target.hide();
                }
                else {
                    $target.addClass(css_class);
                    $target.show();
                }

            }
        })
    }

    /**
     *
     * toggleSubscriberList
     *
     */
    function removeFromList(list) {

        var $trigger = $('.smmg-subscriber-list-remove');

        $trigger.click(function (e) {


            // get Data
            var subscriber_id = this.dataset.subscriberId;
            var subscriber_tag_id = this.dataset.subscriberTagId;

            console.log('subscriber_tag_id',subscriber_tag_id );
            console.log('subscriber_tag_id',subscriber_id );

            var subscriber_group_name = drupalSettings.subscribers[subscriber_tag_id].title;
            var subscriber_name = drupalSettings.subscribers[subscriber_tag_id]['members'][subscriber_id];

            $('.ajax-container').html('<strong>' + subscriber_name + '</strong> von der Liste <strong>' + subscriber_group_name + '</strong> entfernen...');


            $.ajax({
                url: Drupal.url('smmg/toggle_subscriber_tag/' + subscriber_id + '/' + subscriber_tag_id),
                type: 'GET',
                data: {},
                dataType: 'json',
                success: function (results) {

                    console.log(results);

                    var message = results[0];


                    if (message.method === 'removeClass') {
                        $('.ajax-container').addClass('active').html('<strong>' + subscriber_name +
                            '</strong> wurde aus der Gruppe <strong>' + subscriber_group_name + '</strong> entfernt');
                        $('.smmg-subscriber-member-' + subscriber_id).hide()
                    }
                    else {
                        $(message.selector).html(message.data);

                    }
                    return results;

                }
            });


        })
    }


})(jQuery, Drupal, drupalSettings);



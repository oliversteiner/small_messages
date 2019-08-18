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

          $('#smmg-send-message-trigger').click(() => {
            console.log('sendMessagetrigger');
            sendMessage();
          });



        });
    },
  };

  /**
   *
   */
  function startTask() {
    const $elem_button = $('.smmg-start-task-trigger');
    const taskID = $elem_button.data('taskId');

    $elem_button.text('läuft...');
    setIcon('wait');

    const url = '/smmg/task/run/' + taskID;
    $.ajax({
      dataType: 'json',
      url: url,
      success: function(result) {
        console.log('Result', result);
        updateTask(result);
      },
    });
  }

  function updateTask() {

    setIcon('ok');
    $('.smmg-start-task-trigger').text('beendet');
    $('.smmg-start-task-trigger').parent().addClass('task-done')

  }

  /**
   *
   */
  function sendMessage() {
    const messageID = $('#smmg-send-message-trigger').data('messageId');
    console.log('sendMessage', messageID);

    // empty warning messages
    $('.smmg-message-status-warning').text();
    $('.smmg-message-status-warning').hide();
    $('.smmg-task-box').show();

    setIcon('wait');

    // change Page Title
    $('.title.page-title').text('Newsletter wird verarbeitet');
    const url = '/smmg/task/add/' + messageID;
    $.ajax({
      dataType: 'json',
      url: url,
      success: function(result) {
        console.log('Result', result);
        update(result);
      },
    });
  }

  /**
   *
   *
   * @param result
   *
   *  TASK Variables
   *
   *  - number_of_tasks
   *  - generated_tasks
   *  - number_of_subscribers
   *  - tasks
   *      - number
   *      - part_of
   *      - group
   *      - message_id
   *      - message_title
   *      - range_from
   *      - range_to
   *      - task_io
   */
  function update(result) {

    $('.title.page-title').text('Newsletter-Versand aktiv');
    $('.smmg-server-status').text('OK');
    $('.number_of_tasks').text(result.number_of_tasks);
    $('.number_of_subscribers').text(result.number_of_subscribers);

    const list = $('ul.smmg-task-list');

    list.text('');

    const tasks = result.tasks;

    $.each(tasks, function(i) {
      const tasknumber = i + 1;
      const title = 'Task ' + tasknumber;
      const message_id = tasks[i].message_id;
      const number = tasks[i].number;
      const part_of = tasks[i].part_of;
      const range_from = tasks[i].range_from;
      const range_to = tasks[i].range_to;
      const from_to = 'von ' + range_from + ' bis ' + range_to;
      const task_id = tasks[i].task_id;
      const date = new Date();
      const next_action = roundToHour(date);
      next_action.setHours(next_action.getHours() + i);

      const li = $('<li/>').appendTo(list);

      // Start Button
      if (i === 0) {
        // only first item
        const elem_from_to = $('<div/>')
          .addClass('smmg-task-list-button btn-tag smmg-start-task-trigger')
          .attr('role', 'button')
          .attr('data-task-id', task_id)
          .text('jetzt starten')
          .appendTo(li);
      }

      // Title
      const elem_title = $('<h3/>')
        .addClass('smmg-task-list-title')
        .text(title)
        .appendTo(li);

      // from-to
      const elem_from_to = $('<div/>')
        .addClass('smmg-task-list-number')
        .text(from_to)
        .appendTo(li);

      // time
      const elem_time = $('<div/>')
        .addClass('smmg-task-list-time')
        .text('Ausführung: ' + next_action.toLocaleTimeString(navigator.language, {
          hour: '2-digit',
          minute: '2-digit',
        }))
        .appendTo(li);


    });

    setIcon('ok');


    const new_list = $('ul.swing li');
    const max = new_list.length;
    let i = 0;

    const animateListID = setInterval(animate, 700);

    function animate() {
      if (i >= max) {
        clearInterval(animateListID);
      } else {
        const elem = new_list[i];
        $(elem).addClass('show');
      }
      i++;
    }

    $('.smmg-start-task-trigger').click(() => {
      console.log('start Task');
      startTask();
    });

  }

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

  /**
   *
   * @param icon 'wait', 'ok', 'error'
   */
  function setIcon(icon) {

    const $spinner = $('.smmg-icon-spinner');
    const $ok = $('.smmg-icon-ok');
    const $error = $('.smmg-icon-error');

    $spinner.hide();
    $ok.hide();
    $error.hide();

    switch (icon) {
      case 'wait':
        $spinner.show();
        break;

      case 'ok':
        $ok.show();
        break;

      case 'error':
        $error.show();
        break;

      default:
        break;

    }
  }

})(jQuery, Drupal, drupalSettings);

// https://stackoverflow.com/questions/7293306/how-to-round-to-nearest-hour-using-javascript-date-object
function roundToHour(date) {
  p = 60 * 60 * 1000; // milliseconds in an hour
  return new Date(Math.round(date.getTime() / p) * p);
}

<?php

namespace Drupal\small_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\small_messages\Utility\Email;
use Drupal\small_messages\Utility\Helper;
use Drupal\small_messages\Utility\SendInquiryTemplateTrait;

/**
 * Class MessageController.
 */
class MessageController extends ControllerBase
{
  use SendInquiryTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName()
  {
    return 'small_messages';
  }

  protected function emailTest(): bool
  {
    // Build Settings Name
    $module_settings_route = $this->getModuleName() . '.settings';

    // Load Settings
    $config = \Drupal::config($module_settings_route);
    $config_email_test = $config->get('email_test');

    // Return true if "Test mode" checked in settings
    return $config_email_test ? true : false;
  }

  public static function generateMessagePlain(
    $text,
    $search_keys,
    $placeholders,
    $template_nid
  )
  {
    // load Design Template
    $entity = \Drupal::entityTypeManager()->getStorage('node');

    $design_template_content = $entity
      ->get('field_smmg_template_plain_text')
      ->getValue();
    $design_template = $design_template_content[0]['value'];

    // insert Message in to Design Template
    $template_with_message = str_replace('@_text_@', $text, $design_template);
    $body_content = $template_with_message;

    // Replace all Placeholders with Values
    foreach ($search_keys as $index => $search_key) {
      $replace = $placeholders[$index];
      $body_content = str_replace($search_key, $replace, $body_content);
    }

    // Output
    return $body_content;
  }

  public function startRun($message_nid = null)
  {
    $output = [];
    $message = [];

    if (!empty($message_nid) || null != $message_nid) {
      // Message

      // load Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($message_nid);

      // Title
      if ($entity !== null) {
        $message['id'] = $entity->id();

        $message['title'] = $entity->label();

        $message['template_nid'] = Helper::getFieldValue(
          $entity,
          'smmg_design_template'
        );

        $message_html = $entity->get('field_smmg_message_text')->getValue();
        $message['message_html'] = $message_html[0]['value'];
        $message['plaintext'] = $message_html[0]['value'];
      }

      // subscribers
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_group)) {
        // Load all items
        $subscriber_groups_items = $entity
          ->get('field_smmg_subscriber_group')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscriber[] = $item['target_id'];
        }
      }

      $group_index = 0;
      $output['message'] = $message;

      // Addresses

      // load Groups
      $entity_groups = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple($subscriber);

      $list = [];
      $unique_list = [];

      $list_index = 0;

      // Proceed Groups
      foreach ($entity_groups as $group) {
        $term_id = $group->id();

        // get all
        $node_subscripters = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties([
            'type' => 'smmg_member',
            'field_smmg_subscriber_group' => $term_id,
          ]);

        foreach ($node_subscripters as $entity) {
          $id = $entity->id();

          if (in_array($id, $unique_list)) {
            // skip this id
          } else {
            $unique_list[] = $id;

            // email
            $email = [];
            if (!empty($entity->field_email)) {
              $email = $entity->get('field_email')->getValue();
              $email = $email[0]['value'];
            }

            // first_name
            $first_name = [];
            if (!empty($entity->field_first_name)) {
              $first_name = $entity->get('field_first_name')->getValue();
              $first_name = $first_name[0]['value'];
            }

            // last_name
            $last_name = [];
            if (!empty($entity->field_last_name)) {
              $last_name = $entity->get('field_last_name')->getValue();
              $last_name = $last_name[0]['value'];
            }

            $list[$list_index]['id'] = $id;
            $list[$list_index]['title'] = $entity->label();
            $list[$list_index]['last_name'] = $last_name;
            $list[$list_index]['first_name'] = $first_name;
            $list[$list_index]['email'] = $email;

            $list_index++;
          } // else
        } // foreach
      }

      // Email Content
      $data['title'] = $output['message']['title'];
      $data['message_plain'] = $output['message']['plaintext'];
      $data['message_html'] = $output['message']['message_html'];
      $data['from'] = Email::getEmailAddressesFromConfig(
        $this->getModuleName()
      );

      // Send email for every email-address
      foreach ($list as $address) {
        // To
        $data['to'] = $address['email'];

        // replace Placeholders
        $plaintext = $output['message']['plaintext'];
        $plaintext_proceeded = Email::replacePlaceholderInText(
          $plaintext,
          $address
        );

        // Plain Text
        $data['message_plain'] = $plaintext_proceeded;

        // Combine Message with HTML Design Template
        if ($this->emailTest()) {
          $message_html = Email::generateMessageHtml($message, true); // render only body
        } else {
          $message_html = Email::generateMessageHtml($message); // render with HTML-HEAD
        }

        // replace Placeholders
        $message_html_proceeded = Email::replacePlaceholderInText(
          $message_html,
          $address
        );

        // Add to Data
        $data['message_html'] = $message_html_proceeded;
        $module = $this->getModuleName();

        // Test without send
        if ($this->emailTest()) {
          // Display email
          $build = Email::showEmail($module, $data);
          break; // stop after first proceed
        } else {
          // continue to send email

          dpm($data);
          Email::sendNewsletterMail($module, $data);

          $build = $output;
        }
      }
    } else {
      // Error
      $build = [
        '#markup' => $this->t('Error - Message ID: ' . $message_nid),
      ];
    }

    return $build;
  }

  /**
   * @return array
   *
   */
  public function sendMessageTest($nid)
  {
    $data = 'TEST';

    $this->sendmail($this->getModuleName(), $data);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('sendMessage:'),
    ];
  }

  /**
   * @return mixed
   */
  public function sandboxPage()
  {
    $result = $this->startRun(1191);

    // Form mit test

    $form['list'] = [
      '#markup' =>
        '<p>Sandbox</p>' .
        '<hr>' .
        '<div class="sandbox"><pre>' .
        $result['message']['title'] .
        '</pre></div>' .
        '<hr>',
    ];
    $form['sortable'] = [
      '#theme' => 'item_list',
      '#items' => $result['unique'],
      '#attributes' => ['id' => 'sortable'],
    ];

    return $form;
  }

  function sendmail($data)
  {
    $params['title'] = $data['title'];
    $params['message_plain'] = $data['message'];
    $params['message_html'] = $data['message_html'];
    $params['from'] = $data['from'];
    $to = $data['to'];

    // System
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'small_messages';
    $key = 'EMAIL_SMTP'; // Replace with Your key
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    // Send
    $result = $mailManager->mail(
      $module,
      $key,
      $to,
      $langcode,
      $params,
      null,
      $send
    );
    if ($result['result'] != true) {
      $message = t(
        'There was a problem sending your email notification to @email.',
        ['@email' => $to]
      );
      drupal_set_message($message, 'error');
      \Drupal::logger('mail-log')->error($message);
      return;
    } else {
      $message = t('An email notification has been sent to @email.', [
        '@email' => $to,
      ]);
      drupal_set_message($message);
      \Drupal::logger('mail-log')->notice($message);
    }
  }

  public function adminTemplateAction()
  {
  }
}

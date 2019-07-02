<?php

namespace Drupal\small_messages\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
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
   * @param $target_nid
   * @return array
   * @throws Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private static function setSendDate($target_nid): array
  {
    $output = [
      'status' => false,
      'mode' => false,
      'nid' => $target_nid,
      'tid' => false,
    ];

    // Load Node
    $node = Node::load($target_nid);

    if ($target_nid) {
      $node->get('field_smmg_send_date')->value = time();
      $node->get('field_smmg_message_is_send')->value = 1;
    }

    try {
      $node->save();
    } catch (Drupal\Core\Entity\EntityStorageException $e) {
    }

    return $output;
  }

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

  /**
   * @param null $message_nid
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function startRun($message_nid = null)
  {
    $output = [];
    $message = [];
    $template_nid = false;
    $text = '';
    $module = $this->getModuleName();
    $settings_link = Link::createFromRoute(
      t('Config Page'),
      $module . '.settings'
    )->toString();

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

        // Template
        $template_nid = Helper::getFieldValue($entity, 'smmg_design_template');
        $message['template_nid'] = $template_nid;

        // Plaintext and HTML Text
        $text = Helper::getFieldValue($entity, 'smmg_message_text');
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
      $data['from'] = Email::getEmailAddressesFromConfig(
        $this->getModuleName()
      );

      // Send email for every email-address
      foreach ($list as $address) {
        // To
        $data['to'] = $address['email'];

        // replace Placeholders
        $message_plain = Email::replacePlaceholderInText($text, $address);

        // Combine Message with HTML Design Template
        if ($this->emailTest()) {
          $message_html = Email::generateMessageHtml(
            $text,
            $template_nid,
            true
          ); // render only body
        } else {
          $message_html = Email::generateMessageHtml(
            $text,
            $template_nid,
            false
          ); // render with HTML-HEAD
        }

        // replace Placeholders
        $message_html_proceeded = Email::replacePlaceholderInText(
          $message_html,
          $address
        );

        // Add to Data
        $data['message_plain'] = $message_plain;
        $data['message_html'] = $message_html_proceeded;
        $module = $this->getModuleName();

        // Test without send
        if ($this->emailTest()) {
          // Show Warning
          Drupal::messenger()->addWarning(
            t(
              $data['to'] .
              ' - Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
              array('@link' => $settings_link)
            )
          );
          $build = Email::showEmail($module, $data);
          self::setSendDate($message_nid);

          break; // stop after first proceed
        } else {
          // continue to send email

          Email::sendNewsletterMail($module, $data);
          self::setSendDate($message_nid);

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

<?php

namespace Drupal\small_messages\Controller;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Email;
use Drupal\small_messages\Utility\Helper;
use Drupal\small_messages\Utility\SendInquiryTemplateTrait;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class MessageController.
 */
class MessageController extends ControllerBase
{
  use SendInquiryTemplateTrait;

  /**
   * @param $target_nid
   * @return array
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

    if ($target_nid && !empty($node)) {
      $node->get('field_smmg_send_date')->value = time();
      $node->get('field_smmg_message_is_send')->value = 1;
    }

    try {
      $node->save();
    } catch (EntityStorageException $e) {
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getModuleName(): string
  {
    return 'small_messages';
  }

  protected static function emailTest(): bool
  {
    // Build Settings Name
    $module_settings_route = self::getModuleName() . '.settings';

    // Load Settings
    $config = Drupal::config($module_settings_route);
    $config_email_test = $config->get('email_test');

    // Return true if "Test mode" checked in settings
    return $config_email_test ? true : false;
  }

  /**
   * @param null $nid // Message NID
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws Exception
   */
  public function addToTasks($nid = null): JsonResponse
  {
    $max = 200;
    $result = [];

    // Test values
    // $number_of_subscribers = 4685;
    // $message_nid = 719;

    // Check Message ID
    if (empty($nid)) {
      throw new \RuntimeException('No Message Nid given.');
    }

    $node = NODE::load($nid);

    // Message Title
    if (!empty($node)) {
      $message_title = $node->label();
    }

    $all_subscribers = $this->getSubscribersFromMessage($nid, $node);
    $number_of_subscribers = count($all_subscribers);
    // split subscriber to 200 groups
    $number_of_tasks = $number_of_subscribers / $max;

    // round up
    $number_of_tasks = ceil($number_of_tasks);

    // for each 200 make one task
    for ($i = 0; $i < $number_of_tasks; $i++) {
      $task_number = $i + 1;

      // add for every Task 200 more subscriber addresses
      $from = $i * $max + 1;

      // add 199 more.
      $to = $from + ($max - 1);

      // fist
      if ($i === 0) {
        $from = 1;
        $to = $max;
      }

      // last
      if ($task_number === (int)$number_of_tasks) {
        // on the last Task add actual number of Subscribers
        $to = $number_of_subscribers;
      }

      // generate Data
      $data = [
        'task_number' => $task_number,
        'task' => 'Send emails',
        'message_id' => $nid,
        'message_title' => $message_title,
        'from' => $from,
        'to' => $to,
      ];

      try {
        $task = TaskController::newTask($data);

        if ($task) {
          $result[] = $task;
        }
      } catch (Exception $e) {
        // Generic exception handling if something else gets thrown.
        Drupal::logger('Small Messages: addToTasks')->error($e->getMessage());
      }
    }

    // Get number of generated Tasks
    $number_of_results = count($result);

    $response = [
      'Number of Tasks' => $number_of_tasks,
      'Generated Tasks' => $number_of_results,
      'Number of Subscribers' => $number_of_subscribers,
      'Tasks' => $result,
      'test' => $all_subscribers,
    ];

    return new JsonResponse($response);
  }

  /**
   * @param null $message_nid
   * @param int $range_from
   * @param int $range_to
   * @param bool $json
   * @return array|JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function startRun(
    $message_nid,
    $range_from = null,
    $range_to = null,
    $output_mode = 'html'
  )
  {
    $test_send_email_addresses = [];
    $message = [];
    $test_data = [];
    $module = self::getModuleName();
    $settings_link = Link::createFromRoute(
      t('Config Page'),
      $module . '.settings'
    )->toString();

    // Check Message ID
    if (empty($message_nid)) {
      throw new \RuntimeException('No Message Nid given.');
    }

    // load Message
    $node = NODE::load($message_nid);

    // Error if no Node
    if (empty($node)) {
      throw new \RuntimeException('No Message found with Nid %s', $message_nid);
    }

    // id
    $message['id'] = $node->id();

    // title
    $message['title'] = $node->label();

    // Template
    $template_nid = Helper::getFieldValue($node, 'smmg_design_template');
    $message['template_nid'] = $template_nid;

    // Plaintext and HTML Text
    $text = Helper::getFieldValue($node, 'smmg_message_text');

    // subscribers
    $all_subscribers = self::getSubscribersFromMessage($message_nid, $node);
    $number_of_subscribers = count($all_subscribers);

    // process only range of subscribers

    $range_length = $range_to - $range_from;
    $range_subscribers = array_slice(
      $all_subscribers,
      $range_from,
      $range_length,
      true
    );
    $number_of_range_subscribers = count($range_subscribers);

    foreach ($range_subscribers as $id => $email) {
      $data = [];
      $node_subscriber = Node::load($id);

      $first_name = Helper::getFieldValue($node_subscriber, 'first_name');
      $last_name = Helper::getFieldValue($node_subscriber, 'last_name');
      $title = $node->label();

      $address['first_name'] = $first_name;
      $address['last_name'] = $last_name;
      $address['email'] = $email;
      $address['title'] = $title;
      $address['id'] = $id;

      // Combine Message with HTML Design Template
      if (self::emailTest()) {
        $message_html = Email::generateMessageHtml($text, $template_nid, true); // render only body
      } else {
        $message_html = Email::generateMessageHtml($text, $template_nid, false); // render with HTML-HEAD
      }

      // replace Placeholders
      $message_plain = Email::replacePlaceholderInText($text, $address);

      // replace Placeholders
      $message_html_proceeded = Email::replacePlaceholderInText(
        $message_html,
        $address
      );

      // Add to Data
      $data['title'] = $message['title'];
      $data['from'] = Email::getEmailAddressesFromConfig($module);
      $data['to'] = $email;
      $data['message_plain'] = $message_plain;
      $data['message_html'] = $message_html_proceeded;

      $test_send_email_addresses[] = EMAIL::obfuscate_email($email);

      if (!self::emailTest()) {
        // continue to send email
        Email::sendNewsletterMail($module, $data);
      } else {
        $test_data = $data;
      }
    }

    $result = [
      'number_of_range_subscribers' => $number_of_range_subscribers,
      'number_of_subscribers' => $number_of_subscribers,
      'range_from' => $range_from,
      'range_to' => $range_to,
    ];

    // Test without send
    if (self::emailTest()) {
      Drupal::messenger()->addMessage(
        t(
          "<br>$number_of_range_subscribers of $number_of_subscribers Newsletter (from $range_from to $range_to) send to Subscribers<br>"
        )
      );

      // Show Email Test Warning
      Drupal::messenger()->addWarning(
        t(
          'Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
          array('@link' => $settings_link)
        )
      );

      // Show all email-adresses
      Drupal::messenger()->addWarning(
        implode(', ', $test_send_email_addresses)
      );

      // Prepare Twig Template for Browser output
      $build = Email::showEmail($module, $test_data);
    } else {
      // Show all email-adresses
      Drupal::messenger()->addMessage(
        implode(', ', $test_send_email_addresses)
      );
    }
    self::setSendDate($message_nid);

    switch ($output_mode) {
      case 'row':
        return self::returnRow($result);
        break;

      case 'html':
        return self::returnHTML($result, $build);
        break;

      case 'json':
        return self::returnJSON($result);
        break;

      default:
        return self::returnHTML($result, $build);
        break;
    }

  }

  /**
   * @param $result
   * @return array
   */
  public function returnRow($result): array
  {
    return $result;
  }

  /**
   * @param $result
   * @return JsonResponse
   */
  public function returnJSON($result): JsonResponse
  {
    return new JsonResponse($result);
  }

  /**
   * @param $result
   * @return array
   */
  public function returnHTML($result): array
  {

    $number_of_range_subscribers = $result['number_of_range_subscribers'];
    $number_of_subscribers = $result['number_of_subscribers'];
    $range_from = $result['range_from'];
    $range_to = $result['range_to'];

    $build[] = $result;
    $build = [
      '#markup' => "<br>$number_of_range_subscribers of $number_of_subscribers Newsletter (from $range_from to $range_to) send to Subscribers<br>",
    ];


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
    $mailManager = Drupal::service('plugin.manager.mail');
    $module = 'small_messages';
    $key = 'EMAIL_SMTP'; // Replace with Your key
    $langcode = Drupal::currentUser()->getPreferredLangcode();
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
      Drupal::logger('mail-log')->error($message);
      return;
    } else {
      $message = t('An email notification has been sent to @email.', [
        '@email' => $to,
      ]);
      drupal_set_message($message);
      Drupal::logger('mail-log')->notice($message);
    }
  }

  /**
   *
   */
  public function adminTemplateAction(): void
  {
  }

  /**
   * @param bool $date
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws Exception
   */
  public function deleteImportedNodes($date = false): JsonResponse
  {
    // TODO: Add Date-Filter

    $bundle = 'smmg_member';
    $vid = 'smmg_member_type';
    $term_name = 'import';
    $number_of_deleted = 0;
    $number_of_proceeded_nodes = 0;
    $max = 200; // Prepend Server from Memory out
    $step = 1;

    $import_tid = Helper::getTermIDByName($term_name, $vid);

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(array('type' => $bundle));

    $number_of_nodes = count($nodes);

    foreach ($nodes as $node) {
      if ($step < $max) {
        $number_of_proceeded_nodes++;
        $subscriber_groups = Helper::getFieldValue(
          $node,
          'member_type',
          null,
          true
        );
        if (in_array($import_tid, $subscriber_groups, false)) {
          try {
            $node->delete();
            $number_of_deleted++;
          } catch (EntityStorageException $e) {
          }
        }
        $step++;
      }
    }

    $response = [
      'Bundle' => $bundle,
      'Vid' => $vid,
      'Tid of Term import' => $import_tid,
      'Number of Nodes' => $number_of_nodes,
      'Number of proceeded Nodes' => $number_of_proceeded_nodes,
      'Number of deleted Nodes' => $number_of_deleted,
    ];

    return new JsonResponse($response);
  }

  /**
   * @param $nid
   * @param bool $node
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function getSubscribersFromMessage($nid, $node = false): array
  {
    $_all_subscribers = [];

    // Load Message
    if (!$node) {
      $node = NODE::load($nid);
    }

    // get subscriber tags
    $field_name = 'smmg_subscriber_group';
    $subscriber_groups = Helper::getFieldValue($node, $field_name, false, true);

    // all members with the subscriber tags of message
    foreach ($subscriber_groups as $group_id) {
      // get all Subscriber with this tag
      $node_subscribers = Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'smmg_member',
          'field_smmg_subscriber_group' => $group_id,
        ]);

      // get id and email from members
      // put in array: id => email
      foreach ($node_subscribers as $node_subscriber) {
        $email = Helper::getFieldValue($node_subscriber, 'email');

        $_all_subscribers[$node_subscriber->id()] = $email;
      }
    }

    // error if nothing found
    if (count($_all_subscribers) === 0) {
      throw new \RuntimeException(
        sprintf('No subscribers for Message with Message ID: %s', $nid)
      );
    }

    // remove duplicates
    $all_subscribers = array_unique($_all_subscribers);

    // return
    return $all_subscribers;
  }
}

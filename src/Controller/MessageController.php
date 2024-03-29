<?php

namespace Drupal\small_messages\Controller;

use DateTime;
use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Link;
use Drupal\mollo_email\Controller\EmailController;
use Drupal\mollo_utils\Utility\MolloUtils;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Email;
use Drupal\small_messages\Utility\SendInquiryTemplateTrait;
use Drupal\smmg_member\Models\Member;
use Drupal\smmg_newsletter\Models\Newsletter;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class MessageController.
 */
class MessageController extends ControllerBase
{
  use SendInquiryTemplateTrait;

  private static function updateMemberNewsletterData($nid_message, $nid_member): void
  {
    $node = Node::load($nid_member);

    if ($node) {
      $json_data = MolloUtils::getFieldValue($node, Member::field_telemetry);
      $open_date_timestamp = time();

      $data = json_decode($json_data, true);


      $new_data = [];
      // Add new entry
      foreach ($data as $item) {

        if ($item && (int)$item['messageId'] === (int)$nid_message) {
          $item['open'] = true;
          $item['openTS'] = $open_date_timestamp;
          Drupal::logger('Mollo Newsletter')->info(t('Update Telemetry; Member: '.$nid_member.' - Message: '.$nid_message));
        }
        $new_data[] = $item;
      }

      try {
        $node->set(Member::field_telemetry, json_encode($new_data));
        $node->save();
      } catch (EntityStorageException $e) {
        Drupal::logger('Mollo Newsletter')->warning(t('Can\'t update Telemetry; Member: '.$nid_member.' - Message: '.$nid_message));

      }
    }
  }

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
      $node->get(Newsletter::field_send_date)->value = time();
      $node->get(Newsletter::field_is_send)->value = 1;
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
    $message_title = '';
    $send_date = '';

    // Test values
    // $number_of_subscribers = 4685;
    // $message_nid = 719;

    // Check Message ID
    if (empty($nid)) {
      throw new \RuntimeException('No Message Nid given.');
    }

    $message_node = NODE::load($nid);

    // Message Title
    if (!empty($message_node)) {
      $message_title = $message_node->label();
      $send_date = MolloUtils::getFieldValue($message_node, Newsletter::field_send_date);
    }

    $all_subscribers = $this->getSubscribersFromMessage($nid, $message_node);
    $number_of_subscribers = count($all_subscribers);
    // split subscriber to 200 groups
    $number_of_tasks = $number_of_subscribers / $max;

    // round up
    $number_of_tasks = ceil($number_of_tasks);

    // uuid for related tasks
    $uuid = uniqid('mollo_task_', true);

    // for each 200 make one task
    for ($i = 0; $i < $number_of_tasks; $i++) {
      $task_number = $i + 1;

      // add for every Task 200 more subscriber addresses
      $range_from = $i * $max + 1;

      // add 199 more.
      $range_to = $range_from + ($max - 1);

      // fist
      if ($i === 0) {
        $range_from = 1;
        $range_to = $max;
      }

      // last
      if ($task_number === (int)$number_of_tasks) {
        // on the last Task add actual number of Subscribers
        $range_to = $number_of_subscribers;
      }

      $message = [
        'id' => (int)$nid,
        'title' => $message_title,
        'category' => 'Newsletter',
        'send' => $send_date,

      ];

      $range = [
        'from' => (int)$range_from,
        'to' => (int)$range_to,
      ];

      // generate Data
      $data = [
        'number' => $task_number,
        'part_of' => $number_of_tasks,
        'category' => 'Newsletter',
        'related' => $uuid,
        'message' => $message,
        'message_title' => $message_title,
        'range' => $range,
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
      'number_of_tasks' => $number_of_tasks,
      'generated_tasks' => $number_of_results,
      'number_of_subscribers' => $number_of_subscribers,
      'tasks' => $result,
      // 'test' => $all_subscribers,
    ];

    return new JsonResponse($response);
  }

  /**
   * @param null $message_nid
   * @param int $range_from
   * @param int $range_to
   * @param string $output_mode
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
    $template_nid = MolloUtils::getFieldValue($node, 'smmg_design_template');
    $message['template_nid'] = $template_nid;

    // Plaintext and HTML Text
    $text = MolloUtils::getFieldValue($node, 'smmg_message_text');

    // subscribers
    $all_subscribers = self::getSubscribersFromMessage($message_nid, $node);
    $number_of_subscribers = count($all_subscribers);

    // get Invalid Emails
    $invalid_emails =[];
    $config_email = Drupal::config('smmg_newsletter.settings');
    $config_invalid_email_string = $config_email->get('invalid_email');
    $list = explode(',', $config_invalid_email_string);
    foreach ($list as $mail) {
      $invalid_emails[] = trim($mail);
    }


    // process only range of subscribers

    $range_length = $range_to - $range_from;
    $range_subscribers = array_slice(
      $all_subscribers,
      $range_from,
      $range_length,
      true
    );
    $number_of_range_subscribers = count($range_subscribers);

    foreach ($range_subscribers as $member_nid => $email) {
      $data = [];
      $newsletter = false;
      $node_subscriber = Node::load($member_nid);
      $email_invalid = in_array($email, $invalid_emails, false);

      if($node_subscriber){
        $newsletter = MolloUtils::getFieldValue($node_subscriber, 'field_smmg_accept_newsletter');
      }

      if (!$newsletter) {
        Drupal::logger('Newsletter')->warning('Member ' . $member_nid . ' do not want Newsletter');
      }

      if ($email_invalid) {
        Drupal::logger('Newsletter')->warning('Email ' . $email . ' is in invalid addresses list');
      }

      if (!\Drupal::service('email.validator')->isValid($email)) {
        Drupal::logger('Newsletter')->warning( t('Member ' . $member_nid .': The email address %mail is not valid.', array('%mail' => $email)));
        continue;
      }


      if ($newsletter && !$email_invalid) {
        $first_name = MolloUtils::getFieldValue($node_subscriber, 'first_name');
        $last_name = MolloUtils::getFieldValue($node_subscriber, 'last_name');

        $title = $node->label();

        $address['first_name'] = $first_name;
        $address['last_name'] = $last_name;
        $address['email'] = $email;
        $address['title'] = $title;
        $address['id'] = $member_nid;

        // Combine Message with HTML Design Template
        if (self::emailTest()) {
          $message_html = Email::generateMessageHtml(
            $message_nid,
            0,
            $text,
            $template_nid,
            true
          ); // render only body
        } else {
          $message_html = Email::generateMessageHtml(
            $message_nid,
            $member_nid,
            $text,
            $template_nid,
            false
          ); // render with HTML-HEAD
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
          $section = 'newsletter';
        } else {
          $test_data = $data;
          $test = true;
        }

        // add Newsletter Entry to Member in Field Data
        $telemetry = MolloUtils::getFieldValue(
          $node_subscriber,
          Member::field_telemetry,
          false,
          true
        );

        if (is_string($telemetry)) {
          $newsletter_send_data = json_decode($telemetry, true);
        } else {
          $newsletter_send_data = array();
        }

        $test = self::emailTest() ? true : false;

        $telemetry_new = Member::buildTelemetry($newsletter_send_data, $message_nid, $test);

        try {
          $node_subscriber->set(Member::field_telemetry, json_encode($telemetry_new));
          $node_subscriber->save();
        } catch (EntityStorageException $e) {
        }
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
        return self::returnHTML($result);
        break;

      case 'json':
        return self::returnJSON($result);
        break;

      default:
        return self::returnHTML($result);
        break;
    }
  }


  /**
   * @param $result
   * @return array
   */
  public static function returnRow($result): array
  {
    return $result;
  }

  /**
   * @param $result
   * @return JsonResponse
   */
  public static function returnJSON($result): JsonResponse
  {
    return new JsonResponse($result);
  }

  /**
   * @param $result
   * @return array
   */
  public static function returnHTML($result): array
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
    $vid = 'smmg_subscriber_group';
    $term_name = 'Import';
    $number_of_deleted = 0;
    $number_of_proceeded_nodes = 0;
    $range_max = 100; // Prepend Server from Memory out
    $step = 1;

    $import_tid = MolloUtils::getTermIDByName($term_name, $vid);

    // Count all Member Nodes
    $query = Drupal::entityTypeManager()->getStorage('node');
    $count_result = $query
      ->getQuery()
      ->condition('type', $bundle)
      ->count()
      ->execute();
    $number_of_nodes = $count_result;

    // get all Nids of Imported Members
    $query = Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query
      ->getQuery()
      ->condition('type', $bundle)
      ->condition(Member::field_subscriber_group, $import_tid)
      ->range(0, $range_max)
      ->execute();

    foreach ($query_result as $id) {
      $number_of_proceeded_nodes++;
      $node = node::load($id);

      if (!empty($node)) {
        $node->delete();
        $number_of_deleted++;
      }
    }

    $response = [
      'Bundle' => $bundle,
      'Vid' => $vid,
      'Tid of Term import' => $import_tid,
      'Number of Nodes' => $number_of_nodes,
      'Number of proceeded Nodes' => $number_of_proceeded_nodes,
      'Number of deleted Nodes' => $number_of_deleted,
      //    'nids' => $query_result,
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
    $subscriber_groups = MolloUtils::getFieldValue($node, $field_name, false, true);

    // all members with the subscriber tags of message
    foreach ($subscriber_groups as $group_id) {
      $bundle = 'smmg_member';

      // Query with entity_type.manager (The way to go)
      $query = Drupal::entityTypeManager()->getStorage('node');
      $query_result = $query
        ->getQuery()
        ->condition('type', $bundle)
        ->condition('field_smmg_subscriber_group', $group_id)
        ->condition('field_email', null, 'IS NOT NULL')
        ->sort('created', 'ASC')
        ->execute();

      $number_of_nodes = count($query_result);

      $node_subscribers = Node::loadMultiple($query_result);

      // get id and email from members
      // put in array: id => email
      foreach ($node_subscribers as $node_subscriber) {
        $email = MolloUtils::getFieldValue($node_subscriber, 'email');

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

  /**
   * @param bool $date
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   *
   * @route
   */
  public function processImports($date = false): JsonResponse
  {
    $bundle = 'smmg_member';
    $number_of_proceeded_nodes = 0;
    $max = 500; // Prepend Server from Memory out
    $step = 1;

    // Query with entity_type.manager (The way to go)
    $query = Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query
      ->getQuery()
      ->condition('type', $bundle)
      ->condition('field_smmg_token', '')
      ->sort('created', 'ASC')
      ->execute();

    $number_of_nodes = count($query_result);

    $nodes = Node::loadMultiple($query_result);

    foreach ($nodes as $node) {
      if ($step < $max) {
        $number_of_proceeded_nodes++;

        try {
          $node->set('field_smmg_accept_newsletter', 0);
          $node->set('field_smmg_token', MolloUtils::generateToken());
          $node->save();
        } catch (EntityStorageException $e) {
        }
        $step++;
      }
    }

    $response = [
      'Bundle' => $bundle,
      'Number of Nodes' => $number_of_nodes,
      'Number of proceeded Nodes' => $number_of_proceeded_nodes,
    ];

    return new JsonResponse($response);
  }

  static function telemetry($base64): Response
  {
    [$nid_message, $nid_member] = self::unserializeTelemetry($base64);

    self::updateMemberNewsletterData($nid_message, $nid_member);

    $file =
      "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    $filename = 'telemetry.gif';
    $response = new Response();
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $filename
    );
    $response->headers->set('Content-Disposition', $disposition);
    $response->headers->set('Content-Type', 'image/gif');
    $response->setContent($file);

    return $response;
  }

  /**
   * @param $nid_message
   * @param $nid_member
   * @return string
   */
  static function serializeTelemetry($nid_message, $nid_member): string
  {
    $value = [$nid_message, $nid_member];
    $data = implode(',', $value);

    return base64_encode($data);
  }

  /**
   * @param $base64
   * @return array
   */
  static function unserializeTelemetry($base64): array
  {
    $data = base64_decode($base64);
    return explode(',', $data);
  }
}

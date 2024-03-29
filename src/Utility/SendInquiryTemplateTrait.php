<?php

namespace Drupal\small_messages\Utility;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Link;
use Drupal\mollo_utils\Utility\MolloUtils;

/**
 *
 * @see \Drupal\Core\Render\Element\InlineTemplate
 * @see https://www.drupal.org/developing/api/8/localization
 */
trait SendInquiryTemplateTrait
{
  /**
   * {@inheritdoc}
   */

  /**
   * Generate a render array with our Admin content.
   *
   * @return array
   *   A render array.
   */
  public function prepareSend($nid)
  {
    // check Testmode in Settings:
    $module = 'smmg_newsletter'; // TODO Clear things between 'Messages'- and 'Newsletter'-Module
    $config = Drupal::config($module.'.settings');
    $config_email_test = $config->get('email_test');



    // Testmode - Dont send email to Subscriber if "test mode" is checked on settings page.
    if ($config_email_test === 1) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'),
        $module . '.settings'
      )->toString();
      Drupal::messenger()->addWarning(
        t(
          ' Test mode active. No e-mails are sent to the subscribers. Disable Test Mode on @link.',
          array('@link' => $link)
        )
      );
    }


    $template_path = $this->getSendInquiryTemplatePath();
    $template = file_get_contents($template_path);
    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $this->getSendInquiryVariables($nid),
        '#attached' => [
          'drupalSettings' => [
            'subscribers' => $this->getSubscriberJsData($nid),
          ],
          'library' => ['small_messages/small_messages.send_inquiry'],
        ],
      ],
    ];
    return $build;
  }

  /**
   * Name of our module.
   *
   * @return string
   *   A module name.
   */
  protected function getModuleName(): string
  {
    return 'small_messages';
  }

  /**
   * Variables to act as context to the twig template file.
   *
   * @return array
   *   Associative array that defines context for a template.
   */
  protected function getSendInquiryVariables($nid)
  {
    // Module
    $variables['module'] = $this->getModuleName();

    // Message
    $message = $this->getMessageData($nid);
    $variables['message'] = $message;

    // Subscriber Groups
    $subscriber_groups = $this->getSubscriberData($nid);
    $variables['subscriber'] = $subscriber_groups; // id, name, number, list

    return $variables;
  }

  /**
   * Get full path to the template.
   *
   * @return string
   *   Path string.
   */
  protected function getSendInquiryTemplatePath()
  {
    return drupal_get_path('module', $this->getModuleName()) .
      '/templates/smmg-send-inquiry.html.twig';
  }

  /**
   * @param $nid
   *
   * @return mixed
   *
   */
  protected function getMessageData($nid)
  {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);

    // NID
    $nid = $entity->id();

    // Title
    $title = $entity->label();

// Template
    $template_nid = MolloUtils::getFieldValue(
      $entity,
      'smmg_design_template'
    );

    // Text
    // Plaintext and HTML Text
    $text = MolloUtils::getFieldValue(
      $entity,
      'smmg_message_text'
    );
    $message_nid = $nid;
    $member_nid = 0;

    $message_html = Email::generateMessageHtml($message_nid, $member_nid, $text, $template_nid, true);


    // Send Date
    $send_date = false;
    if (!empty($entity->field_smmg_send_date)) {
      // Load
      $send_date = $entity->get('field_smmg_send_date')->value;
    }


    // Is Send
    $is_send = false;
    if (!empty($entity->field_smmg_message_is_send)) {
      // Load
      $is_send = $entity->get('field_smmg_message_is_send')->value;
    }

    // subscriber
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


    $output['id'] = $nid;
    $output['title'] = $title;
    $output['subscriber'] = $subscriber;
    $output['send_date'] = $send_date;
    $output['is_send'] = $is_send;
    $output['message_plain'] = $text;
    $output['message_html'] = $message_html;


    return $output;
  }

  /**
   * @param $nid
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  protected function getSubscriberData($nid)
  {
    // load Message
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);
    // subscriber
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
    $output = [];

    // load Groups
    $entity_groups = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($subscriber);
    $i = 0;

    // Proceed Groups
    foreach ($entity_groups as $group) {
      $term_id = $group->id();
      $title = $group->label();

      // get all
      $node_subscripters = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'smmg_member',
          'field_smmg_subscriber_group' => $term_id,
        ]);

      $number = count($node_subscripters);

      $list = [];
      $list_index = 0;
      foreach ($node_subscripters as $item) {

        $email = MolloUtils::getFieldValue($item, 'email');

        $list[$list_index]['id'] = $item->id();
        $list[$list_index]['name'] = $item->label();
        $list[$list_index]['email'] = $email;
        $list_index++;
      }

      // Output

      $output[$group_index]['id'] = $term_id;
      $output[$group_index]['title'] = $title;
      $output[$group_index]['number'] = $number;
      $output[$group_index]['list'] = $list;

      $group_index++;
    }

    return $output;
  }


  function getSubscriberAddresses()
  {

  }

  protected function getSubscriberJsData($nid)
  {
    // load Message
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);
    // subscriber
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
    $output = [];

    // load Groups
    $entity_groups = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($subscriber);
    $i = 0;

    // Proceed Groups
    foreach ($entity_groups as $group) {
      $term_id = $group->id();
      $title = $group->label();

      // get all
      $node_subscripters = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'smmg_member',
          'field_smmg_subscriber_group' => $term_id,
        ]);

      $nummer = count($node_subscripters);

      $list = [];
      foreach ($node_subscripters as $item) {
        $sub_id = $item->id();
        $sub_name = $item->label();

        $list[$sub_id] = $sub_name;
      }

      // Output

      $output[$term_id]['title'] = $title;
      $output[$term_id]['number'] = $nummer;
      $output[$term_id]['members'] = $list;

      $group_index++;
    }

    return $output;
  }
}

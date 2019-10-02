<?php

namespace Drupal\small_messages\Models;

use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Helper;
use Drupal\smmg_member\Models\Member;
use Drupal\smmg_newsletter\Controller\NewsletterController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Newsletter
 * @package Drupal\smmg_newsletter\Models
 *
 * Fields
 * -----------------------------
 *  - field_smmg_design_template
 *  - field_smmg_message_is_send
 *  - field_smmg_message_is_template
 *  - field_smmg_message_group
 *  - field_smmg_message_text
 *  - field_smmg_send_date
 *  - field_smmg_subscriber_group
 *
 *
 */
class Message
{
  private $data;
  private $title;
  private $node;
  private $id;
  private $created;
  private $changed;

  public const type = 'smmg_message';

  /* Drupal Fields */
  public const field_design_template = 'field_smmg_design_template';
  public const field_is_send = 'field_smmg_message_is_send';
  public const field_send_date = 'field_smmg_send_date';
  public const field_is_template = 'field_smmg_message_is_template';
  public const field_group = 'field_smmg_message_group';
  public const field_text = 'field_smmg_message_text';
  public const field_subscriber_group = 'field_smmg_subscriber_group';
  /**
   * @var array|bool|string
   */
  private $is_send;
  /**
   * @var array|bool|string
   */
  private $is_template;
  /**
   * @var array|bool|string
   */
  private $send_date;
  /**
   * @var array|bool|string
   */
  private $text;
  /**
   * @var array|bool|string
   */
  private $subscriber_group;
  /**
   * @var array|bool|string
   */
  private $design_template;

  public function __construct($nid)
  {
    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->changed = false;
    $this->done = false;
    $this->active = false;

    $node = Node::load($nid);
    $this->node = $node;

    if (!empty($node)) {
      $this->id = $node->id();
      $this->title = $node->label();
      $this->created = $node->getCreatedTime();
      $this->changed = $node->getChangedTime();
      $this->is_send = Helper::getFieldValue($node, self::field_is_send);
      $this->is_template = Helper::getFieldValue(
        $node,
        self::field_is_template
      );
      $this->group = Helper::getFieldValue(
        $node,
        self::field_group,
        'smmg_message_group',
        'full'
      );
      $this->text = Helper::getFieldValue($node, self::field_text);
      $this->send_date = Helper::getFieldValue($node, self::field_send_date);
      $subscriber_groups = Helper::getFieldValue(
        $node,
        self::field_subscriber_group,
        'smmg_subscriber_group',
        'full'
      );
      $this->design_template = Helper::getFieldValue(
        $node,
        self::field_design_template
      );
    }

    $new_subscriber_groups = [];
    foreach ($subscriber_groups as $group) {
      $new_group = $group;
      $new_group['subscribers'] = (int)self::countMembers(Message::field_subscriber_group, $group['id']);
      $new_subscriber_groups[] = $new_group;
    }
    $this->subscriber_group = $new_subscriber_groups;

    // Counts
    $count = [
      'all' => 0,
      'read' => 0,
      'error' => 0,
      'unsubscribe' => 0,
    ];

    $this->data = [
      'id' => (int)$this->id,
      'title' => $this->title,
      'created' => (int)$this->created,
      'changed' => (int)$this->changed,
      'category' => $this->group,
      'text' => $this->text,
      'isSend' => (bool)$this->is_send,
      'send' => (int)$this->send_date,
      'isTemplate' => (bool)$this->is_template,
      'subscriberGroups' => $this->subscriber_group,
      'designTemplate' => (int)$this->design_template,
      'count' => $count,
    ];
  }

  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @return array
   */
  public function getData(): array
  {
    return $this->data;
  }

  /**
   * @return JsonResponse
   */
  public function getJson(): JsonResponse
  {
    return new JsonResponse($this->data);
  }

  /**
   * @return bool
   */
  public function created(): bool
  {
    return $this->created;
  }

  public static function countMembers($field, $tid)
  {
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_count = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->condition($field, $tid)
      ->count()
      ->execute();

    return $query_count;
  }
}

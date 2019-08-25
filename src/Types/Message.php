<?php

namespace Drupal\small_messages\Types;

use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Helper;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Class Newsletter
 * @package Drupal\smmg_newsletter\Types
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
  public const field__is_send = 'field_smmg_message_is_send';
  public const field__is_template = 'field_smmg_message_is_template';
  public const field_group = 'field_smmg_message_group';
  public const field_text = 'field_smmg_message_text';
  public const field_send_date = 'field_smmg_send_date';
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
      $this->is_send = Helper::getFieldValue($node, self::field__is_send);
      $this->is_template = Helper::getFieldValue($node, self::field__is_template);
      $this->group = Helper::getFieldValue($node, self::field_group, 'smmg_message_group', 'full');
      $this->text = Helper::getFieldValue($node, self::field_text);
      $this->send_date = Helper::getFieldValue($node, self::field_send_date);
      $this->subscriber_group = Helper::getFieldValue($node, self::field_subscriber_group, 'smmg_subscriber_group', 'full');
      $this->design_template = Helper::getFieldValue($node, self::field_design_template);
    }


    $this->data = [
      'id' => (int)$this->id,
      'title' => $this->title,
      'created' => (int)$this->created,
      'changed' => (int)$this->changed,
      'group' => $this->group,
      'text' => $this->text,
      'is_send' => (bool)$this->is_send,
      'is_template' => (bool)$this->is_template,
      'send_date' => $this->send_date,
      'subscriber_group' => $this->subscriber_group,
      'design_template' => (int)$this->design_template,
    ];
  }

  public
  function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @return array
   */
  public
  function getData(): array
  {
    return $this->data;
  }

  /**
   * @return JsonResponse
   */
  public
  function getJson(): JsonResponse
  {
    return new JsonResponse($this->data);
  }

  /**
   * @return bool
   */
  public
  function created(): bool
  {
    return $this->created;
  }


}

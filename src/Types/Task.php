<?php

namespace Drupal\small_messages\Types;

use Drupal;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Helper;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;


class Task
{
  private $data;
  private $title;
  private $node;
  private $id;
  /**
   * @var bool
   */
  private $created;
  /**
   * @var bool
   */
  private $done;
  /**
   * @var bool
   */
  private $active;

  /* Drupal fields */
  public const field_active = 'field_smmg_is_active';
  public const field_json = 'field_smmg_is_active';
  public const field_done = 'field_smmg_is_active';


  public function __construct($nid)
  {

    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->done = false;
    $this->active = false;
    $data_json = [];

    $node = Node::load($nid);
    $this->node = $node;

    if (!empty($node)) {
      $this->id = $node->id();
      $this->title = $node->label();
      $this->created = $node->getCreatedTime();
      $data_json = Helper::getFieldValue($node, self::field_json);
      $this->done = Helper::getFieldValue($node, self::field_done);
      $this->active = Helper::getFieldValue($node, self::field_active);
    }

    $data = json_decode($data_json, true);

// Message
    $message['id'] = (int)$data['message_id'];
    $message['title'] = $data['message_title'];

// Range
    $range['from'] = (int)$data['range_from'];
    $range['to'] = (int)$data['range_to'];

    $this->data = [
      'id' => (int)$this->id,
      'title' => $this->title,
      'created' => $this->created,
      'done' => (boolean)$this->done,
      'active' => (boolean)$this->active,
      'number' => (int)$data['number'],
      'part_of' => (int)$data['part_of'],
      'group' => (string)$data['group'],
      'message' => $message,
      'range' => $range,


    ];

  }

  public function getTitle(): ?string
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

  /**
   * @return bool
   */
  public
  function isDone(): bool
  {
    return $this->done;
  }

  /**
   * @return bool
   */
  public
  function isActive(): bool
  {
    return $this->active;
  }

  /**
   * @return int
   * @throws EntityStorageException
   */
  public function setToDone(): int
  {
    $this->node->set(self::field_done, 1);
    try {
      $this->node->save();
      return $this->id;
    } catch (Exception $e) {
      Drupal::messenger()->addError('Error: Cant set Task to done');
      return false;
    }
  }

  /**
   * @return int
   * @throws EntityStorageException
   */
  public function setToUndone(): int
  {
    $this->node->set(self::field_done, 0);
    try {
      $this->node->save();
      return $this->id;
    } catch (Exception $e) {
      Drupal::messenger()->addError('Error: Cant set Task to undone');
      return false;
    }
  }

  /**
   * @return int
   * @throws EntityStorageException
   */
  public function setToActive(): int
  {
    $this->node->set(self::field_active, 1);
    try {
      $this->node->save();
      return $this->id;
    } catch (Exception $e) {
      Drupal::messenger()->addError('Error: Cant set Task to Active');
      return false;
    }
  }

  /**
   * @return int
   * @throws EntityStorageException
   */
  public function setToInactive(): int
  {
    $this->node->set(self::field_active, 0);
    try {
      $this->node->save();
      return $this->id;
    } catch (Exception $e) {
      Drupal::messenger()->addError('Error: Cant set True to inactive');
      return false;
    }
  }
}

<?php

namespace Drupal\small_messages\Models;

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
  private $created;
  private $changed;
  private $done;
  private $active;

  /* Drupal fields */
  public const field_active = 'field_smmg_is_active';
  public const field_json = 'field_data';
  public const field_done = 'field_smmg_is_done';


  public function __construct($nid)
  {

    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->changed = false;
    $this->done = false;
    $this->active = false;
    $data_json = [];

    $node = Node::load($nid);
    $this->node = $node;

    if (!empty($node)) {
      $this->id = $node->id();
      $this->title = $node->label();
      $this->created = $node->getCreatedTime();
      $this->changed = $node->getChangedTime();
      $data_json = Helper::getFieldValue($node, self::field_json);
      $this->done = Helper::getFieldValue($node, self::field_done);
      $this->active = Helper::getFieldValue($node, self::field_active);
    }


    $message['id'] = 0;
    $message['title'] = '';

    $related = '';

    $data = json_decode($data_json, true);
    if (isset($data)) {

      // Related
      if (isset($data['related'])) {
        $related = $data['related'];
      }

      // Message

      $message['category'] = (string)$data['group'];

      if (isset($data['message'])) {
        if (isset($data['message']['id'])) {
          $message['id'] = (int)$data['message']['id'];
        }
        if (isset($data['message']['title'])) {
          $message['title'] = $data['message']['title'];
        }
      }

      // TODO Deprecated
      // Message
      if (isset($data['message_id'])) {
        $message['id'] = (int)$data['message_id'];
      }
      if (isset($data['message_title'])) {
        $message['title'] = $data['message_title'];
      }


// Range
      if (isset($data['range'])) {
        if (isset($data['range']['from'])) {
          $range['from'] = (int)$data['range']['from'];
        }
        if (isset($data['range']['to'])) {
          $range['to'] = $data['range']['to'];
        }
      }

      // TODO Deprecated
// Range
      if (isset($data['range_from'])) {
        $range['from'] = (int)$data['range_from'];
      }
      if (isset($data['range_to'])) {
        $range['to'] = $data['range_to'];
      }

    }


    $this->data = [
      'id' => (int)$this->id,
      'title' => $this->title,
      'created' => $this->created,
      'changed' => $this->changed,
      'done' => (boolean)$this->done,
      'active' => (boolean)$this->active,
      'related' => $related,
      'number' => (int)$data['number'],
      'part_of' => (int)$data['part_of'],
      'group' => (string)$data['group'], // TODO Deprecated
      'message' => $message,
      'range' => $range,
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
  public
  function setToDone(): int
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
  public
  function setToUndone(): int
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
  public
  function setToActive(): int
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
  public
  function setToInactive(): int
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

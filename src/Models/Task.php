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
  private $title;
  private $node;
  private $id;
  private $created;
  private $changed;
  private $done;
  private $active;
  private $telemetry;

  public const type = 'smmg_task';

  /* Drupal fields */
  public const field_active = 'field_smmg_is_active';
  public const field_telemetry = 'field_smmg_telemetry';
  public const field_done = 'field_smmg_is_done';
  public const field_done_timestamp = 'field_smmg_is_done_timestamp';

  public function __construct($nid)
  {
    $this->id = 0;
    $this->title = '';
    $this->created = false;
    $this->changed = false;
    $this->done = false;
    $this->active = false;
    $this->telemetry = [];
    $_telemetry = [];

    $node = Node::load($nid);
    $this->node = $node;

    if (!empty($node)) {
      $this->id = $node->id();
      $this->title = $node->label();
      $this->created = $node->getCreatedTime();
      $this->changed = $node->getChangedTime();
      $_telemetry = Helper::getFieldValue($node, self::field_telemetry);
      $_telemetry = json_decode($_telemetry, true);

      $this->done = Helper::getFieldValue($node, self::field_done);
      $this->active = Helper::getFieldValue($node, self::field_active);
    }

    $message['id'] = 0;
    $message['title'] = '';
    $message['category'] = '';
    $related = '';
    $range = [];

    if (isset($_telemetry)) {
      // Related
      if (isset($_telemetry['related'])) {
        $related = $_telemetry['related'];
      }

      // Message
      if (isset($_telemetry['message'])) {
        if (isset($_telemetry['message']['id'])) {
          $message['id'] = (int) $_telemetry['message']['id'];
        }
        if (isset($_telemetry['message']['title'])) {
          $message['title'] = $_telemetry['message']['title'];
        }
        if (isset($_telemetry['message']['send'])) {
          $message['send'] = (int)$_telemetry['message']['send'];
        }
        if (isset($_telemetry['message']['category'])) {
          $message['category'] = $_telemetry['message']['category'];
        }
      }

      // Range
      if (isset($_telemetry['range'])) {
        if (isset($_telemetry['range']['from'])) {
          $range['from'] = (int) $_telemetry['range']['from'];
        }
        if (isset($_telemetry['range']['to'])) {
          $range['to'] = $_telemetry['range']['to'];
        }
      }
    }

    $this->telemetry = [
      'id' => (int) $this->id,
      'title' => $this->title,
      'created' => $this->created,
      'changed' => $this->changed,
      'done' => (bool) $this->done,
      'active' => (bool) $this->active,
      'related' => $related,
      'number' => (int) $_telemetry['number'],
      'part_of' => (int) $_telemetry['part_of'],
      'message' => $message,
      'range' => $range
    ];
  }

  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @return array
   */
  public function getTelemetry(): array
  {
    return $this->telemetry;
  }

  public function getJson(): JsonResponse
  {
    return new JsonResponse($this->telemetry);
  }

  /**
   * @return bool
   */
  public function created(): bool
  {
    return $this->created;
  }

  /**
   * @return bool
   */
  public function isDone(): bool
  {
    return $this->done;
  }

  /**
   * @return bool
   */
  public function isActive(): bool
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

<?php

namespace Drupal\small_messages\Controller;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\small_messages\types\Task;
use Drupal\small_messages\Utility\Email;
use Drupal\small_messages\Utility\Helper;
use Exception;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TaskController.
 *
 * Node Bundle and Fields:
 *
 * smmg_task
 * ---------------------------
 *  - field_data (JSON)
 *  - field_smmg_is_active (Boolean)
 *  - field_smmg_is_done (Boolean)
 *
 */
class TaskController extends ControllerBase
{
  /**
   * {@inheritdoc}
   */
  protected function getModuleName()
  {
    return 'small_messages';
  }

  /**
   * Add.
   *
   * @return array
   *   Return Hello string.
   */
  public function add()
  {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: add'),
    ];
  }

  /**
   * Remove.
   *
   * @return array
   *   Return Hello string.
   */
  public function remove()
  {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: remove'),
    ];
  }

  /**
   * Cron Test.
   *
   * @return JsonResponse
   */
  public function runTaskTest(): JsonResponse
  {
    $title = 'Cron Test';
    $build_plain = $title;
    $message_html = $title;

    $data = [
      'title' => $title,
      'message_plain' => $build_plain,
      'message_html' => $message_html,
    ];

    $response[] = $title;
    $module = 'smmg_newsletter';

    Email::sendAdminMail($module, $data);

    return new JsonResponse($response);
  }


  /**
   *
   * @throws \Exception
   *
   * get all Tasks from DB
   *
   */
  public function getTasks()
  {
    $tasks = [];

    // Query with entity_type.manager (The way to go)
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('type', 'smmg_task')
      ->sort('created', 'ASC')
      ->execute();

    $number_of_tasks = count($query_result);

    if ($number_of_tasks === 0) {
      $response = ['message' => 'no tasks found', 'tasksCount' => 0];
      return new JsonResponse($response);
    }


    foreach ($query_result as $nid) {
      $task = new Task($nid);

      $tasks[] = $task->getData();
    }

    $response = [
      'tasks' => $tasks,
      'tasksCount' => $number_of_tasks,
    ];

    return new JsonResponse($response);
  }


  /**
   *
   * @throws \Exception
   *
   * get unfinished Tasks from DB
   * run next task, mark it "done"
   *
   */
  public function runTasks(): JsonResponse
  {
    // Query with entity_type.manager (The way to go)
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('type', 'smmg_task')
      ->condition('field_smmg_is_active', '1')
      ->condition('field_smmg_is_done', '0')
      ->sort('created', 'ASC')
      ->execute();

    // count Tasks
    $number_of_active_tasks = count($query_result);
    $first_id = reset($query_result);

    if ($number_of_active_tasks > 0) {
      return $this->runTask($first_id);
    }

    $response = ['message' => 'no open tasks found'];
    return new JsonResponse($response);
  }


  public function runTask($nid): JsonResponse
  {
    $task_id = $nid;
    $task_title = '';
    $task_data = '';
    $is_done = 0;


    $node = Node::load($nid);

    if (!empty($node)) {
      $task_id = $node->id();
      $task_title = $node->label();
      $task_data = Helper::getFieldValue($node, 'data');
      $is_done = Helper::getFieldValue($node, 'smmg_is_done');
    }


    $response = [
      'task_id' => $task_id,
      'task_title' => $task_title,
      'task_data' => $task_data,
    ];

// json data to array
    $data = json_decode($task_data, true);

    $message_nid = $data['message_id'];
    $range_from = $data['range_from'];
    $range_to = $data['range_to'];

    if ($range_from === 1) {
      $range_from = 0;
    }

    $result = MessageController::startRun($message_nid, $range_from, $range_to, 'row');

    if (!empty($result['error'])) {
      throw new RuntimeException($result['error']);
    }


    $response['result'] = $result;

    // Set Task to done
    try {
      $node->set('field_smmg_is_done', 1);
      $node->set('field_smmg_is_done_timestamp', time());

      $node->save();
    } catch (EntityStorageException $e) {
    }

// Admin Mail
    $admin_text = "<h1>Task Run erfolgreich</h1>";
    $admin_text .= "<h2>$task_title</h2>";
    $admin_text .= "<table style='text-align: left'>";

    foreach ($result as $key => $value) {
      $admin_text .= "<tr>";
      $admin_text .= "    <th>$key</th><td>$value</td>";
      $admin_text .= "</tr>";

    }
    $admin_text .= "</table>";

    $admin_mail_data = [
      'title' => 'Task Run: ' . $task_title,
      'message_plain' => $admin_text,
      'message_html' => $admin_text,
    ];


    Email::sendAdminMail($this->getModuleName(), $admin_mail_data);

    return new JsonResponse($response);
  }

  /**
   * @param $data
   * @return bool
   * @throws Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function newTask($data)
  {
    $short_title = substr($data['message_title'], 0, 8) . '...';

    $title = sprintf(
      '%s: %s (%s) - From %s to %s',
      $data['group'],
      $short_title,
      $data['message_id'],
      $data['range_from'],
      $data['range_to']
    );

    if ($data) {
      $node = Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
          'type' => 'smmg_task',
          'status' => 0, //not  published or not
          'promote' => 0, //not promoted to front page
          'title' => $title,
          'field_smmg_is_active' => 1,
          'field_smmg_is_done' => 0,
          'field_data' => json_encode($data),
        ]);

      // Save
      try {
        $node->save();
        $nid = $node->id();
        $data['task_id'] = (int)$nid;
      } catch (EntityStorageException $e) {
        return false;
      }
    }

    return $data;
  }

  /**
   * @param bool $date
   * @return JsonResponse
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws Exception
   */
  public function removeAllTasks($date = false): JsonResponse
  {
    // TODO: Add Date-Filter

    $bundle = 'smmg_task';
    $number_of_deleted = 0;
    $number_of_proceeded_nodes = 0;
    $max = 200; // Prepend Server from Memory out
    $step = 1;


    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(array('type' => $bundle));

    $number_of_nodes = count($nodes);

    foreach ($nodes as $node) {
      if ($step < $max) {
        $number_of_proceeded_nodes++;
        try {
          $node->delete();
          $number_of_deleted++;
        } catch (EntityStorageException $e) {
        }
        $step++;
      }
    }

    $response = [
      'Bundle' => $bundle,
      'Number of Nodes' => $number_of_nodes,
      'Number of proceeded Nodes' => $number_of_proceeded_nodes,
      'Number of deleted Nodes' => $number_of_deleted,
    ];

    return new JsonResponse($response);
  }
}

<?php

namespace Drupal\small_messages\Controller;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\mollo_utils\Utility\MolloUtils;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Models\Task;
use Drupal\small_messages\Utility\Email;
use Drupal\smmg_member\Models\Member;
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
 *  - field_smmg_is_done_timestamp (ts)
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
  public function getTasks(): JsonResponse
  {
    $name = 'Newsletter Tasks';
    $action = 'list';
    $path = '';
    $base = 'smmg/api/tasks/';
    $version = '1.0.1';
    $tasks = [];
    $message = '';

    // Query with entity_type.manager (The way to go)
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('type', Task::type)
      ->sort('created', 'DESC')
      ->execute();

    $number_of = count($query_result);

    if ($number_of === 0) {
      $response = ['message' => 'no tasks found', 'count' => 0];
      return new JsonResponse($response);
    }


    foreach ($query_result as $nid) {
      $task = new Task($nid);

      $tasks[] = $task->getTelemetry();
    }


    $response = [
      'name' => $name,
      'path' => $base . $path,
      'version' => $version,
      'action' => $action,
      'message' => $message,
      'count' => $number_of,
      'tasks' => $tasks,
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
      ->condition('type', Task::type)
      ->condition(Task::field_active, '1')
      ->condition(Task::field_done, '0')
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
    $name = 'Run Task';
    $action = 'run';
    $id = $nid;
    $path = 'run/';
    $base = 'smmg/api/task/';
    $version = '1.0.0';
    $message = '';
    $label = '';
    $telemetry = '';
    $is_done = 0;


    $node_task = Node::load($nid);

    if (!empty($node_task)) {
      $id = $node_task->id();
      $label = $node_task->label();
      $telemetry = MolloUtils::getFieldValue($node_task, Task::field_telemetry);
      $is_done = MolloUtils::getFieldValue($node_task, Task::field_done);
    }

    $task = [
      'id' => $id,
      'label' => $label,
      'telemetry' => $telemetry,
    ];

    $response = [
      'name' => $name,
      'path' => $base . $path . $id,
      'version' => $version,
      'action' => $action,
      'message' => $message,
      'task' => $task,
    ];

// json data to array
    $data = json_decode($telemetry, true);

    $message_nid = $data['message']['id'];
    $range_from = $data['range']['from'];
    $range_to = $data['range']['to'];

    if ($range_from === 1) {
      $range_from = 0;
    }

    $result = MessageController::startRun($message_nid, $range_from, $range_to, 'row');

    if (!empty($result['error'])) {
      // throw new RuntimeException($result['error']);
      $response['error'] = 'run Task failed';
      $response['message'] = $result;

    }


    $response['result'] = $result;

    // Set Task to done
    try {
      $node_task->set(Task::field_done, 1);
      $node_task->set(Task::field_done_timestamp, time());

      $node_task->save();
    } catch (EntityStorageException $e) {
    }

// Admin Mail
    $admin_text = "<h1>Task Run erfolgreich</h1>";
    $admin_text .= "<h2>$label</h2>";
    $admin_text .= "<table style='text-align: left'>";

    foreach ($result as $key => $value) {
      $admin_text .= "<tr>";
      $admin_text .= "    <th>$key</th><td>$value</td>";
      $admin_text .= "</tr>";

    }
    $admin_text .= "</table>";

    $admin_mail_data = [
      'title' => 'Task Run: ' . $label,
      'message_plain' => $admin_text,
      'message_html' => $admin_text,
    ];


    Email::sendAdminMail($this->getModuleName(), $admin_mail_data);

    return new JsonResponse($response);
  }

  /**
   * @param $data
   * @return array
   * @throws Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function newTask($data): array
  {


    if ($data) {

      if (isset($data['message_title'])) {
        $short_title = substr($data['message_title'], 0, 8) . '...';
      } else {
        $short_title = 'No Title';
      }

      $title = sprintf(
        '%s: %s (%s) - From %s to %s',
        $data['category'],
        $short_title,
        $data['message']['id'],
        $data['range']['from'],
        $data['range']['to']
      );


      $node = Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
          'type' => Task::type,
          'status' => 0, //not  published or not
          'promote' => 0, //not promoted to front page
          'title' => $title,
          Task::field_active => 1,
          Task::field_done => 0,
          Task::field_telemetry => json_encode($data),
        ]);

      // Save
      try {
        $node->save();
        $nid = $node->id();
        $data['task_id'] = (int)$nid;
      } catch (EntityStorageException $e) {
        return ['error' => 'Creating task failed'];
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

    $bundle = Task::type;
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

public static function APIDelete($id = null): JsonResponse{


  $name = 'Delete Task';
  $action = 'delete';
  $path = 'delete';
  $base = 'smmg/api/task/';
  $version = '1.0.0';

  $response = [
    'name' => $name,
    'path' => $base . $path,
    'version' => $version,
    'action' => $action,
  ];

  // Delete
  $delete = Task::delete($id);

  // Result
  if ($delete) {
    $response['message'] = 'Task successfully deleted.';
    $response['id'] = $id;
  }else{
    $response['error'] = true;
    $response['message'] = 'Task could not be deleted';
  }

  return new JsonResponse($response);}
}


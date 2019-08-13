<?php

namespace Drupal\small_messages\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\small_messages\Utility\Email;
use Drupal\small_messages\Utility\Helper;
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
   * @return JsonResponse Return Hello string.
   *   Return Hello string.
   */
  public function cron_test()
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

    Email::sendAdminMail($this->getModuleName(), $data);

    return new JsonResponse($response);
  }

  /**
   *
   * @throws \Exception
   */
  public function runTask(): JsonResponse
  {
    $task_id = 0;
    $task_title = '';
    $task_data = '';

    // get unfinished Tasks from DB
    // run next task, mark it "done"
    // return status
    // end


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


    // processing fifo - Load first Task
    $node = Node::load($first_id);

    if (!empty($node)) {
      $task_id = $node->id();
      $task_title = $node->label();
      $task_data = Helper::getFieldValue($node, 'data');
    }

    $response = [
      'Number of active tasks' => $number_of_active_tasks,
      'Task ID' => $task_id,
      'task Title' => $task_title,
      'Task Data' => $task_data,
      //  'query_result' => $query_result,
    ];

// json data to array
    $data = json_decode($task_data, true);

    $message_nid = $data['message_nid'];
    $range_from = $data['from'];
    $range_to = $data['to'];

    $result = MessageController::startRun($message_nid, $range_from, $range_to, 'row');

    if ($result['error']) {
      throw new RuntimeException($result['error']);
    }

    $response['result'] = $result;

    // Set Task to done
    try{
    $node->set('field_smmg_is_done',1);
    $node->save();
    } catch (EntityStorageException $e) {
    }


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
      '%s - %s (%s) - From: %s to: %s',
      $data['task'],
      $short_title,
      $data['message_id'],
      $data['from'],
      $data['to']
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
        $data['task_id'] = $nid;
      } catch (EntityStorageException $e) {
        return false;
      }
    }

    return $data;
  }
}

<?php

  namespace Drupal\small_messages\Controller;

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\Core\Ajax\InvokeCommand;
  use Drupal\Core\Ajax\ReplaceCommand;
  use Drupal\Core\Controller\ControllerBase;
  use Drupal\small_messages\Utility\SubscriberTrait;

  /**
   * Controller routines for page example routes.
   */
  class SubscriberController extends ControllerBase {


    /**
     * {@inheritdoc}
     */
    protected function getModuleName() {
      return 'small_messages';
    }


    /**
     * @return \Drupal\Core\Ajax\AjaxResponse
     */
    public function toggleSubsciberTag($target_nid, $subscriber_tag_tid) {


      $result = SubscriberTrait::toggleSubscriberTag($target_nid, $subscriber_tag_tid);


      $response = new AjaxResponse();
      $selector = '#subscibe-group-' . $target_nid . '-' . $subscriber_tag_tid;

      if ($result['mode'] == 'add') {
        $response->addCommand(new InvokeCommand($selector, 'addClass', ['active']));

      }

      elseif ($result['mode'] == 'remove') {
        $response->addCommand(new InvokeCommand($selector, 'removeClass', ['active']));

      }

      else {
        $message = 'Es ist ein Fehler aufgetreten beim ändern der Empfängergruppe';
        $response->addCommand(new ReplaceCommand('.ajax-container', '<div class="ajax-container">' . $message . '</div>'));

      }

      return $response;

    }


    public function addSubscription($nid = NULL) {

      $result = SubscriberTrait::toggleSubscription($nid, 'add');

      $response = new AjaxResponse();

      if ($result['status'] === TRUE) {
        $message = 'Success : ' . $nid;
        $class = 'success';

      }
      else {
        $message = 'Error : ' . $nid;
        $class = 'error';
      }

      $response->addCommand(new ReplaceCommand('.ajax-container', '<div class="ajax-container ' . $class . '">' . $message . '</div>'));

      return $response;

    }


    public function removeSubscription($nid = NULL) {

      $result = SubscriberTrait::toggleSubscription($nid, 'remove');

      $response = new AjaxResponse();

      if ($result['status'] === TRUE) {
        $message = 'Success : ' . $nid;
        $class = 'success';

      }
      else {
        $message = 'Error : ' . $nid;
        $class = 'error';
      }

      $response->addCommand(new ReplaceCommand('.ajax-container', '<div class="ajax-container ' . $class . '">' . $message . '</div>'));

      return $response;

    }

  }

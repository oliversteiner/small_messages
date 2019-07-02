<?php

  namespace Drupal\small_messages\Controller;

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\Core\Ajax\InvokeCommand;
  use Drupal\Core\Ajax\ReplaceCommand;
  use Drupal\Core\Ajax\SettingsCommand;
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
     * @param $target_nid
     * @param $subscriber_group_tid
     * @return AjaxResponse
     */
    public function toggleSubscriberGroup($target_nid, $subscriber_group_tid): AjaxResponse
    {


      $result = SubscriberTrait::toggleSubscriberGroup($target_nid, $subscriber_group_tid);


      $response = new AjaxResponse();
      $selector = '#subscibe-group-'.$target_nid.'-'.$subscriber_group_tid;

      if ($result['mode'] == 'add') {
        $response->addCommand(new InvokeCommand($selector,'addClass',['active']));

      }

      elseif ($result['mode'] == 'remove') {
        $response->addCommand(new InvokeCommand($selector,'removeClass',['active']));

      }

      else {
        $message = 'Es ist ein Fehler aufgetreten beim ändern der Empfängergruppe';
        $response->addCommand(new ReplaceCommand('.ajax-container', '<div class="ajax-container">' . $message . '</div>'));
        $response->addCommand(new SettingsCommand([
          'toggleSubscriberGroup' => $result,
        ], TRUE));

      }

      return $response;

    }


  }

<?php

namespace Drupal\small_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\small_messages\Utility\MessagesTemplateTrait;
use Drupal\small_messages\Utility\SubscribersTemplateTrait;

/**
 * Controller routines for page example routes.
 */
class AdminController extends ControllerBase {

  use MessagesTemplateTrait;
  use SubscribersTemplateTrait;


  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'small_messages';
  }

  /**
   * @return array
   */
  public function smmgConfig() {

    // Default settings.
    $config = \Drupal::config('small_messages.settings');
    // Page title and source text.
    $page_title = $config->get('small_messages.page_title');
    $source_text = $config->get('small_messages.source_text');

    return [
      '#markup' => '<p>page_title = ' . $page_title . '</p>' .
        '<p>source_text = ' . $source_text . '</p>',
    ];
  }


}

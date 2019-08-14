<?php

/**
 * Implements hook_views_data_alter().
 */
function small_messages_views_data_alter(array &$data) {

  $data['node']['smmg_send_message_button'] = [
    'title' => t('Send Messages Button'),
    'group' => t('Small Messages'),
    'field' => [
      'title' => t('Send Messages Button'),
      'help' => t('Add Button for send message'),
      'id' => 'smmg_send_message_button'
    ],
  ];
  $data['node']['smmg_run_task_button'] = [
    'title' => t('Run Task Button'),
    'group' => t('Small Messages'),
    'field' => [
      'title' => t('Button for Running Tasks'),
      'help' => t('Button for Running Tasks'),
      'id' => 'smmg_run_task_button'
    ],
  ];
  $data['node']['smmg_subscribe_group'] = [
    'title' => t('Subscribe Group'),
    'group' => t('Small Messages'),
    'field' => [
      'title' => t('Subscribe Group'),
      'help' => t('Add tags for Group'),
      'id' => 'smmg_subscribe_group'
    ],
  ];


}




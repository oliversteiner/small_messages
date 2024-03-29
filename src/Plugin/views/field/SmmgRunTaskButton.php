<?php
/**
 * @file
 * Definition of
 * Drupal\small_messages\Plugin\views\field\SmmgSendMessageButton
 */

namespace Drupal\small_messages\Plugin\views\field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mollo_utils\Utility\MolloUtils;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to add Message Send Button.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("smmg_run_task_button")
 */
class SmmgRunTaskButton extends FieldPluginBase
{
  /**
   * @{inheritdoc}
   */
  public function query()
  {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   *
   * @return array
   */
  protected function defineOptions()
  {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   * @param ResultRow $values
   * @return array
   * @throws \Exception
   */
  public function render(ResultRow $values)
  {
    $node = $values->_entity;
    $nid = $values->_entity->id();
    $field_name = 'smmg_is_done';
    $is_send = MolloUtils::getFieldValue($node, $field_name);
    $destination = 'admin/smmg/list/tasks';
    $modal_info['width'] = 700;
    $modal_info['height'] ='90%';

    $elements = [];

    $link = 'smmg/task/run/' . $nid . '?destination=' . $destination;
    $class = [
      'use-ajax',
      'vat-button',
      'vat-button-run-task',
      'btn',
      'btn-sm',
      'btn-default',
    ];

    // if message send
    if ($is_send) {
      $class[] = 'message-is-send';
      $label='Rerun';
    }else{
      $label= 'Run Task';
    }

    $elements['label'] = [
      '#title' => $label,
      '#type' => 'link',
      '#url' => Url::fromUri('internal:/' . $link),
      '#attributes' => [
        'class' => $class,
       'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'height' => $modal_info['height'],
          'width' => $modal_info['width'],
        ]),
        'type' => 'button',
      ],
    ];

    return $elements;
  }
}

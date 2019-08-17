<?php
/**
 * @file
 * Definition of
 * Drupal\small_messages\Plugin\views\field\SmmgSendMessageButton
 */

namespace Drupal\small_messages\Plugin\views\field;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\small_messages\Utility\Helper;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to add Message Send Button.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("smmg_send_message_button")
 */
class SmmgSendMessageButton extends FieldPluginBase
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
    $field_name = 'smmg_message_is_send';
    $is_send = Helper::getFieldValue($node, $field_name);
    $modal_info['width'] = 700;
    $modal_info['height'] = '90%';

    $elements = [];




    $url_obj = Url::fromRoute('small_messages.ajax.prepare_send', ['nid' => $nid]);
    $url = $url_obj->toString();

    $class = [
      'vat-button',
      'vat-button-send',
      'btn',
      'btn-sm',
      'btn-default',
    ];

    // if message send
    if ($is_send) {
      $class[] = 'message-is-send';
      $button_label = 'Nochmals...';
    } else {
      $button_label = 'Senden...';
    }

    $class = implode(' ', $class);

    $send_button = '<a class="' . $class . '" href="' . $url . '">' .
      '<div class="vat-button-send-icon"><i class="fas fa-2x fa-paper-plane"></i></div>' .
      '<div class="vat-button-send-label">' . $button_label . '</div>' .
      '</a>';


    /*
        $elements['send'] = [
          '#title' => $this->t('Send...'),
          '#type' => 'link',
          '#url' => Url::fromUri('internal:/' . $link),
          '#attributes' => [
            'class' => $class,
            'type' => 'button',
          ],
        ];*/

    $elements['send'] = [
      '#markup' => $send_button,
      '#allowed_tags' => ['div', 'a', 'i'],];


    return $elements;
  }
}

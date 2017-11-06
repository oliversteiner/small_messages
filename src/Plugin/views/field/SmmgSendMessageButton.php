<?php
  /**
   * @file
   * Definition of
   * Drupal\small_messages\Plugin\views\field\SmmgSendMessageButton
   */

  namespace Drupal\small_messages\Plugin\views\field;


  use Drupal\Core\Form\FormStateInterface;
  use Drupal\views\Plugin\views\field\FieldPluginBase;
  use Drupal\Core\Url;
  use Drupal\views\ResultRow;

  /**
   * Field handler to add Edit and Delete Buttons.
   *
   * @ingroup views_field_handlers
   *
   * @ViewsField("smmg_send_message_button")
   */
  class SmmgSendMessageButton extends FieldPluginBase {

    /**
     * @{inheritdoc}
     */
    public function query() {
      // Leave empty to avoid a query on this field.
    }

    /**
     * Define the available options
     *
     * @return array
     */
    protected function defineOptions() {
      $options = parent::defineOptions();


      return $options;
    }

    /**
     * Provide the options form.
     */
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {

      parent::buildOptionsForm($form, $form_state);
    }

    /**
     * @{inheritdoc}
     */
    public function render(ResultRow $values) {

      $node = $values->_entity;
      $bundle = $node->bundle();
      $nid = $values->_entity->id();
      $send_date = $node->get('field_smms_send_date')->getValue();
      $destination = 'smmg/messages';

      $elements = [];

      $button_name = 'senden';
      $link = 'smmg/ajax/prepare_send/'.$nid.'?destination=' . $destination;
      $icon_name = 'send';
      $class = ['use-ajax', 'vat-button', 'vat-button-send', 'btn', 'btn-sm', 'btn-default'];

      // if message sended
      if ($send_date[0] != null) {
        $class[] = 'message-sended';

      }


      $elements[$button_name] = [
        '#title' => $this->t($button_name),
        '#type' => 'link',
        '#url' => Url::fromUri('internal:/' . $link),
        '#attributes' => [
          'class' => $class,
          'data-dialog-type' => 'modal',
          'data-vat-icon' => $icon_name,
          'type' => 'button',
        ],
      ];


      return $elements;

    }
  }
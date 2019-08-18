<?php

namespace Drupal\small_messages\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'smmg_json_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "smmg_json_field_widget",
 *   label = @Translation("JSON field widget for Small Messages"),
 *   field_types = {
 *     "text",
 *     "string",
 *     "text_long",
 *     "string_long"
 *   }
 * )
 */
class JSONField extends WidgetBase
{

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    $config = \Drupal::config('ace_editor.settings')->get();
    return $config + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {

    $settings = $this->getSettings();


    /*   $element['value'] = $element + [
           '#type' => 'textarea',
           '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
           '#size' => $this->getSetting('size'),
           '#placeholder' => $this->getSetting('placeholder'),
           '#maxlength' => $this->getFieldSetting('max_length'),
           // Attach libraries as per the setting.
           '#attached' => [
             'library' => [
               'ace_editor/formatter'
             ],
             'drupalSettings' => [
               // Pass settings variable ace_formatter to javascript.
               'ace_formatter' => $settings
             ],
           ],
           '#attributes' => [
             'class' => ['content'],
             'readonly' => 'readonly',
           ],
           '#prefix' => '<div class="ace_formatter">',
           '#suffix' => '<div>',
         ];*/


    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textarea',
      '#default_value' => $value,
      '#size' => 200,
      '#maxlength' => 200,
      '#element_validate' => [
        [static::class, 'validate'],
      ],
      // Attach libraries as per the setting.
      '#attached' => [
        'library' => [
          'ace_editor/primary',  'ace_editor/formatter'
        ],
        'drupalSettings' => [
          // Pass settings variable ace_formatter to javascript.
          'ace_formatter' => $settings
        ],
      ],
      '#attributes' => [
        'class' => ['content'],
       // 'readonly' => 'readonly',
      ],
      '#prefix' => '<div class="ace_formatter">',
      '#suffix' => '</div>',
    ];
    return ['value' => $element];

  }

}

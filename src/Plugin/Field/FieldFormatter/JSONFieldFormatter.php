<?php

namespace Drupal\small_messages\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'smmg_json__field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "smmg_json__field_formatter",
 *   label = @Translation("JSON field field formatter for Small Messages"),
 *   field_types = {
 *     "text",
 *     "string",
 *     "text_long",
 *     "string_long"
 *   },
 *   settings = {
 *     "trim_length" = "600",
 *   },
 *    edit = {
 *      "editor" = "plain_text"
 *    }
 * )
 */
class JSONFieldFormatter extends FormatterBase
{

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings()
  {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {

    $element = [];

    $element['trim_length'] = array(
      '#title' => t('Trim length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#required' => TRUE,
    );

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];

    $summary[] = t('Trim length: @trim_length',
      ['@trim_length' => $this->getSetting('trim_length')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = [];


    foreach ($items as $delta => $item) {


      $elements[$delta] = [
        '#theme' => 'json_field_formatter',
        '#data' => $this->viewValueAsJSON($item),
        '#id' => uniqid('json-field-', false),
      '#attached' => ['library' => ['small_messages/small_messages.json_field_formatter']],
      ];

    }



    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item): string
  {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated.
   */
  protected function viewValueAsJSON(FieldItemInterface $item): array
  {

    $json_string = $item->value;

    return json_decode($json_string, true);
  }

}

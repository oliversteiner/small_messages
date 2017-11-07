<?php
  /**
   * @file
   * Definition of
   * Drupal\small_messages\Plugin\views\field\SmmgSubscribeGroup
   */

  namespace Drupal\small_messages\Plugin\views\field;


  use Drupal\Core\Form\FormStateInterface;
  use Drupal\taxonomy\Entity\Term;
  use Drupal\taxonomy\Entity\Vocabulary;
  use Drupal\views\Plugin\views\field\FieldPluginBase;
  use Drupal\Core\Url;
  use Drupal\views\ResultRow;

  /**
   * Field handler to add Edit and Delete Buttons.
   *
   * @ingroup views_field_handlers
   *
   * @ViewsField("smmg_subscribe_group")
   */
  class SmmgSubscribeGroup extends FieldPluginBase {

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


      $field = 'field_smmg_subscriber_tags';
      $elements = [];
      $default_classes = ['use-ajax', 'btn-tag'];
      $active_tids = [];

      // load all Tags
      $vid = 'newsletter_mailto_group';
      $default_tags = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($vid);

      // Load Active Tags
      $node = $values->_entity;
      $nid = $values->_entity->id();
      $active_tags = $values->_entity->get($field)
        ->getValue();

      // save only tid
      foreach ($active_tags as $active_tag) {
        $active_tids[] = $active_tag['target_id'];
      }


      foreach ($default_tags as $default_tag) {

        $term_id = $default_tag->tid;
        $term_name = $default_tag->name;

        // url for SubscriberController::toggleSubsciberTag'
        $url = Url::fromRoute('small_messages.toggle_subscriber_tag',
          [
            'target_nid' => $term_id,
            'subscriber_tag_tid' => $term_name,
          ]);


        // class
        if (in_array($term_id, $active_tids)) {
          $class = $default_classes;
          array_push($class, 'active');
        }
        else {
          $class = $default_classes;

        }

        // build
        $elements[$term_name] = [
          '#title' => $term_name,
          '#type' => 'link',
          '#url' => Url::fromUri('internal:/' . $url->toString()),
          '#attributes' => [
            'class' => $class,
            'id' => 'subscibe-group-' . $nid . '-' . $term_id,
          ],
        ];


      }
      return $elements;

    }
  }
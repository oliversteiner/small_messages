<?php

  namespace Drupal\small_messages\Utility;

  use Drupal\small_messages\Controller\MessageController;

  /**
   *
   * @see \Drupal\Core\Render\Element\InlineTemplate
   * @see https://www.drupal.org/developing/api/8/localization
   */
  trait SendInquiryTemplateTrait {

    /**
     * {@inheritdoc}
     */

    /**
     * Generate a render array with our Admin content.
     *
     * @return array
     *   A render array.
     */
    public function prepareSend($nid) {


      $template_path = $this->getSendInquiryTemplatePath();
      $template = file_get_contents($template_path);
      $build = [
        'description' => [
          '#type' => 'inline_template',
          '#template' => $template,
          '#context' => $this->getSendInquiryVariables($nid),
          '#attached' => [
            'drupalSettings' => [
              'subscribers' => $this->getSubscriberJsData($nid),
            ],
            'library' => [
              'small_messages/small_messages.send_inquiry',
            ],
          ],
        ],
      ];
      return $build;
    }

    /**
     * Name of our module.
     *
     * @return string
     *   A module name.
     */
    abstract protected function getModuleName();

    /**
     * Variables to act as context to the twig template file.
     *
     * @return array
     *   Associative array that defines context for a template.
     */
    protected function getSendInquiryVariables($nid) {

      // Module
      $variables['module'] = $this->getModuleName();

      // Message
      $message = $this->getMessageData($nid);
      $variables['message'] = $message;

      // Subscriber Groups
      $subscriber_groups = $this->getSubscriberData($nid);
      $variables['subscriber'] = $subscriber_groups; // id, name, number, list


      return $variables;
    }

    /**
     * Get full path to the template.
     *
     * @return string
     *   Path string.
     */
    protected function getSendInquiryTemplatePath() {
      return drupal_get_path('module', $this->getModuleName()) . "/templates/smmg-send-inquiry.html.twig";
    }

    /**
     * @param $nid
     *
     * @return mixed
     *
     */
    public function getMessageData($nid) {


      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);

      // NID
      $nid = $entity->id();

      // Title
      $title = $entity->label();
      // Body
      $text_content = $entity->get('field_smmg_text')->getValue();
      $text_with_format = $text_content[0];
      $text = $text_content[0]['value'];

      // Send Date
      $send_date = [];
      if (!empty($entity->field_smmg_message_send_date)) {
        // Load
        $send_date_content = $entity->get('field_smmg_message_send_date')
          ->getValue();

        $send_date = $send_date_content[0]['value'];
      }

      // Template NID
      $template_id = NULL;
      if (!empty($entity->field_smmg_design_template)) {
        // Load
        $design_template = $entity->get('field_smmg_design_template')
          ->getValue();

        $design_template_id = $design_template[0]['target_id'];

      }


      // subscriber
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_tags)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_tags')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscriber[] = $item['target_id'];
        }
      }


      $search_keys = [
        '@@_id_@@',
        '@@_titel_@@',
        '@@_vorname_@@',
        '@@_nachname_@@',
        '@@_email_@@',
      ];


      $placeholders = [
        '[ID]',
        '<span class="smmg-placeholder">[TITEL]</span>',
        '<span class="smmg-placeholder">[VORNAME]</span>',
        '<span class="smmg-placeholder">[NACHNAME]</span>',
        '<span class="smmg-placeholder">[EMAIL]</span>',
      ];


      $text_plain = MessageController::generateMessagePlain($text, $search_keys, $placeholders, $design_template_id);
      $text_html_body_only = MessageController::generateMessageHtml($text, $search_keys, $placeholders, $design_template_id, true);


      $message['id'] = $nid;
      $message['title'] = $title;
      $message['text'] = $text_with_format; // must be rendered in twig
      $message['text_plain'] = $text_plain;
      $message['text_html_body'] = $text_html_body_only;
      $message['subscriber'] = $subscriber;
      $message['send_date'] = $send_date;
      $message['search_keys'] = $search_keys;
      $message['placeholders'] = $placeholders;


      return $message;
    }





    protected function getSubscriberData($nid) {


      // load Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);
      // subscriber
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_tags)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_tags')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscriber[] = $item['target_id'];
        }
      }


      $group_index = 0;
      $output = [];

      // load Groups
      $entity_groups = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple($subscriber);
      $i = 0;


      // Proceed Groups
      foreach ($entity_groups as $group) {

        $term_id = $group->id();
        $title = $group->label();


        // get all
        $node_subscripters = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties([
            'field_smmg_subscriber_tags' => $term_id,
          ]);

        $nummer = count($node_subscripters);

        $list = [];
        $list_index = 0;
        foreach ($node_subscripters as $item) {
          $list[$list_index]['id'] = $item->id();
          $list[$list_index]['name'] = $item->label();
          $list_index++;
        }

        // Output


        $output[$group_index]['id'] = $term_id;
        $output[$group_index]['title'] = $title;
        $output[$group_index]['number'] = $nummer;
        $output[$group_index]['list'] = $list;

        $group_index++;
      }


      return $output;
    }

    protected function getSubscriberJsData($nid) {


      // load Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);
      // subscriber
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_tags)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_tags')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscriber[] = $item['target_id'];
        }
      }


      $group_index = 0;
      $output = [];

      // load Groups
      $entity_groups = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadMultiple($subscriber);
      $i = 0;


      // Proceed Groups
      foreach ($entity_groups as $group) {

        $term_id = $group->id();
        $title = $group->label();


        // get all
        $node_subscripters = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties([
            'field_smmg_subscriber_tags' => $term_id,
          ]);

        $nummer = count($node_subscripters);

        $list = [];
        foreach ($node_subscripters as $item) {
          $sub_id = $item->id();
          $sub_name = $item->label();

          $list[$sub_id] = $sub_name;

        }

        // Output

        $output[$term_id]['title'] = $title;
        $output[$term_id]['number'] = $nummer;
        $output[$term_id]['members'] = $list;

        $group_index++;
      }


      return $output;
    }

  }



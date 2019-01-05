<?php

  namespace Drupal\small_messages\Utility;

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
    protected function getMessageData($nid) {




      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);

      // NID
      $nid = $entity->id();

      // Title
      $title = $entity->label();

      // Body
      $body = $entity->get('body')->getValue();

      // Send Date
      $send_date = [];
      if (!empty($entity->field_smmg_send_date)) {
        // Load
        $send_date = $entity->get('field_smmg_send_date')
          ->getValue();
      }


      // subscriber
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_group)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_group')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscriber[] = $item['target_id'];
        }
      }


      $output['id'] = $nid;
      $output['title'] = $title;
      $output['body'] = $body[0]; // must be rendert in twig
      $output['subscriber'] = $subscriber;
      $output['send_date'] = $send_date;


      return $output;
    }

    protected function getSubscriberData($nid) {


      // load Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);
      // subscriber
      $subscriber = [];
      if (!empty($entity->field_smmg_subscriber_group)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_group')
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
            'type' => 'goenner',
            'field_smmg_subscriber_group' => $term_id,
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
      if (!empty($entity->field_smmg_subscriber_group)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_group')
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
            'type' => 'goenner',
            'field_smmg_subscriber_group' => $term_id,
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



<?php

  namespace Drupal\small_messages\Utility;


  trait SubscriberTrait {


    /**
     * @param $subscriber_group_nid
     *
     * @return mixed
     */
    public static function getSubscriberGroup($subscriber_group_nid) {


      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($subscriber_group_nid);

      // NID
      $nid = $entity->id();

      // Title
      $title = $entity->label();

      // Twig-Variables
      $subscriber_group = [
        'nid' => $nid,
        'title' => $title,
      ];

      return $subscriber_group;
    }


    /**
     * @param $nid
     *
     * @return mixed
     * @internal param $file_nid
     *
     */
    public static function getSubscriberGroupList($nid) {


      $subscriber_group_nids = [];
      $subscriber_groups = [];

      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

      if (!empty($entity->newsletter_mailto_group)) {
        if (isset($entity->newsletter_mailto_group->entity)) {

          foreach ($entity->newsletter_mailto_group as $subscriber_group) {

            if ($subscriber_group->entity) {
              $subscriber_group_nids[] = $subscriber_group->entity->id();
            }
          }
        }
      }

      if (count($subscriber_group_nids) > 0) {


        // put them in new array
        foreach ($subscriber_group_nids as $subscriber_group_nid) {

          $subscriber_groups[] = self::getSubscriberGroup($subscriber_group_nid);
        }
      }


      return $subscriber_groups;

    }


    /**
     * @param $name
     *
     * @return mixed
     *
     * save new Subscriber_group in db
     *
     */

    public static function newSubscriberGroup($name) {

      $term = [
        'name' => $name,
        'vid' => 'newsletter_mailto_group',
        // 'langcode' => 'de',
      ];

      $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->create($term);
      return $term;
    }

      /**
       * @param $target_nid
       * @param $subscriber_group_nid
       *
       * @return mixed
       *
       *
       * @throws \Drupal\Core\Entity\EntityStorageException
       */
    public static function toggleSubscriberTag($target_nid, $subscriber_group_nid) {

      // 1. error?
      // 2. add or remove
      // 3. node nid
      // 4. Subscriber nid

      $output = [
        'status' => FALSE,
        'mode' => FALSE,
        'nid' => $target_nid,
        'tid' => $subscriber_group_nid,
      ];
      $subscribers = [];



      // Load Node
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($target_nid);

      // Field OK?
      if (!empty($entity->field_smmg_subscriber_tags)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_smmg_subscriber_tags')
          ->getValue();

        // save only tid
        foreach ($subscriber_groups_items as $item) {
          $subscribers[] = $item['target_id'];
        }

        $subscribers_unique = array_unique($subscribers);  // PERFORMANS ?
        $position = array_search($subscriber_group_nid, $subscribers_unique);


        if ($position !== FALSE) {

          // Remove Item
          unset($subscribers_unique[$position]);

          $output['mode'] = 'remove';
        }
        else {

          // Add Item
          $subscribers_unique[] = $subscriber_group_nid;

          $output['mode'] = 'add';

        }

        // delete field
        unset($entity->field_smmg_subscriber_tags);

        // fill field new
        foreach ($subscribers_unique as $tid) {
          $item['target_id'] = $tid;
          $entity->field_smmg_subscriber_tags[] = $item;
        }


        $entity->save();
        $output['status'] = TRUE;

      }


      return $output;

    }

    static function toggleSubscription($nid = NULL, $mode = 'toggle') {
      // Mode: toggle, add, remove

      $output = [
        'status' => FALSE,
        'mode' => $mode,
        'nid' => $nid,
      ];

      $field_name = 'field_smmg_accept_newsletter';

      if ($nid) {


        // Load Node
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($nid);

        // Field OK?
        if (!empty($entity->{$field_name})) {
          $entity_value = $entity->get($field_name)
            ->getValue();
          $actual_value = $entity_value[0];
        }
        else {
          $actual_value = NULL;
        }

        // set value
        switch ($mode) {

          case 'toggle':
            $actual_value == 1 ? $value = 0 : $value = 1;
            break;

          case 'add':
            $value = 1;
            break;

          case 'remove':
            $value = 0;
            break;

          default:
            $value = $actual_value;
            break;
        }

        // Apply changes
        $entity->$field_name->setValue($value);
        $entity->save();
        $output['status'] = TRUE;

      }
      else {
        // empty nid

      }

      return $output;

    }

  }
<?php

namespace Drupal\small_messages\Utility;

use Drupal\mollo_utils\Utility\MolloUtils;

trait SubscriberTrait
{
  /**
   * @param $subscriber_group_nid
   *
   * @return mixed
   */
  public static function getSubscriberGroup($subscriber_group_nid)
  {
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @internal param $file_nid
   */
  public static function getSubscriberGroupList($nid)
  {
    $subscriber_group_nids = [];
    $subscriber_groups = [];

    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($nid);

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

  public static function newSubscriberGroup($name)
  {
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function toggleSubscriberGroup(
    $target_nid,
    $subscriber_group_nid
  ) {


    $output = [
      'status' => false,
      'mode' => false,
      'nid' => $target_nid,
      'tid' => $subscriber_group_nid,
    ];

    // Load Node
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($target_nid);

    try {
      $subscribers = MolloUtils::getFieldValue($entity, 'smmg_subscriber_group');
      $subscribers_unique = array_unique($subscribers); // PERFORMING ?
      $position = array_search(
        $subscriber_group_nid,
        $subscribers_unique,
        true
      );

      if ($position === false) {
        // Add Item
        $subscribers_unique[] = $subscriber_group_nid;
        $output['mode'] = 'add';
      } else {
        // Remove Item
        unset($subscribers_unique[$position]);
        $output['mode'] = 'remove';
      }

      // delete field
      unset($entity->field_smmg_subscriber_group);

      // fill field new
      foreach ($subscribers_unique as $tid) {
        $item['target_id'] = $tid;
        $entity->field_smmg_subscriber_group[] = $item;
      }

      $entity->save();
      $output['status'] = true;
    } catch (\Exception $e) {
    }

    // }

    return $output;
  }
}

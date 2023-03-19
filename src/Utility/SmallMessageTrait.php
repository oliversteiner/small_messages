<?php

namespace Drupal\small_messages\Utility;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\taxonomy\Entity\Term;

trait SmallMessageTrait
{
  /**
   * @param $name
   * @return bool|number
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function getOrigin($name)
  {
    $vid = 'smmg_origin';
    $tid = 0;

    $term_list = [];
    $term_names = [];

    // Load Origin Terms
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    foreach ($terms as $term) {
      $term_list[$term->name] = $term->tid;
      $term_names[] = $term->name;
    }

    // add Term $name if not in list
    if (in_array($name, $term_names, true)) {
      $tid = $term_list[$name];
    } else {
      try {
        $new_tid = Term::create([
          'name' => $name,
          'vid' => $vid,
        ])->save();

        $tid = $new_tid;
      } catch (EntityStorageException $e) {
      }
    }

    // return Term ID
    return $tid;
  }
}

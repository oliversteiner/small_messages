<?php

namespace Drupal\small_messages\Utility;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\small_messages\Exceptions\SmmgHelperException;
use Drupal\taxonomy\Entity\Term;
use Exception;
use http\Message;

class Helper
{
  public static function getTermsByID($vid)
  {
    $term_list = [];
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    foreach ($terms as $term) {
      $term_list[$term->tid] = $term->name;
    }
    return $term_list;
  }

  public static function getTermNameByID($term_id)
  {
    $term = Term::load($term_id);
    $name = $term->getName();

    return $name;
  }

  /**
   * @param $term_name
   * @param $vid
   * @param bool $create
   * @return int
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function getTermIDByName($term_name, $vid, $create = true): int
  {
    $tid = 0;

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    foreach ($terms as $term) {
      if ($term->name === $term_name) {
        $tid = $term->tid;
        break;
      }
    }

    // Create new Term
    if($tid === 0 && $create === true){
      try {
        $new_term = Term::create([
          'name' => $term_name,
          'vid' => $vid,
        ])->save();
        $tid = $new_term;

      } catch (EntityStorageException $e) {
      }
    }

    return $tid;
  }

  /**
   * @param $vid
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function getTermsByName($vid): array
  {
    $term_list = [];
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);
    foreach ($terms as $term) {
      $term_list[$term->name] = $term->tid;
    }
    return $term_list;
  }

  /**
   * @param $name
   * @return bool|number
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function getOrigin($name)
  {
    $vid = 'smmg_origin';
    $tid = false;

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
    if (in_array($name, $term_names)) {
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

  /**
   * @param NodeInterface | Node $node
   * @param string $field_name
   * @param null $term_list
   * @param bool $force_array
   * @return boolean | string | array
   * @throws Exception
   */
  public static function getFieldValue(
    $node,
    $field_name,
    $term_list = null,
    $force_array = false
  ) {
    $result = false;

    try {
      if (!is_object($node)) {
        throw new \RuntimeException(
          'The $node Parameter is not a valid drupal entity.' .
            ' (Field: ' .
            $field_name .
            ' Node:' .
            $node .
            ')'
        );
      }

      if (!is_string($field_name)) {
        // check for 'field_field_NAME'
        $pos = strpos($field_name, 'field_');

        if ($pos === 0) {
          throw new \RuntimeException(
            'Use $field_name without "field_" in HELPER:getFieldValue(' .
              $field_name .
              ')'
          );
        }
      }
    } catch (Exception $e) {
      throw new \RuntimeException(
        '$field_name must be a string.' .
          ' (Field: ' .
          $field_name .
          ' Node:' .
          $node .
          ') ' .
          $e
      );
    }

    $field_name = 'field_' . $field_name;

    try {
      if ($node->get($field_name)) {
        $value = $node->get($field_name)->getValue();

        // single Item
        if (count($value) === 1) {
          // Default Field
          if ($value && $value[0] && isset($value[0]['value'])) {
            $result = $value[0]['value'];
          }

          // Target Field
          if ($value && $value[0] && isset($value[0]['target_id'])) {
            $result = $value[0]['target_id'];
          }

          // Value is Taxonomy Term
          if ($term_list) {
            if (is_string($term_list)) {
              $term_list = self::getTermsByID($term_list);
            }

            if ($term_list && $term_list[$result]) {
              $result = $term_list[$result];
            } else {
              $result = false;
              throw new Exception(
                'No Term found with id ' .
                  $result .
                  ' in Taxonomy ' .
                  $term_list
              );
            }
          }

          if ($force_array) {
            $arr[] = $result;
            $result = $arr;
          }
        }

        // Multiple Items
        $i = 0;
        if (count($value) > 1) {
          foreach ($value as $item) {
            // Standart Field
            if (isset($item['value'])) {
              $result[$i] = $item['value'];
            }

            // Target Field
            if (isset($item['target_id'])) {
              $result[$i] = $item['target_id'];
            }
            $i++;
          }
        }

        // No Items
        if ($force_array && count($value) === 0) {
          $result = [];
        }
      }
    } catch (Exception $e) {
      throw new \RuntimeException(
        'field_name (' . $field_name . ') Error \r' . $e
      );
    }

    return $result;
  }

  public static function getToken($node_or_node_id)
  {
    $field_name = 'field_smmg_token';
    $result = false;

    if (is_numeric($node_or_node_id)) {
      $entity = Node::load($node_or_node_id);
    } else {
      $entity = $node_or_node_id;
    }

    if ($entity->get($field_name)) {
      $value = $entity->get($field_name)->getValue();

      // Standart Field
      if ($value && $value[0] && isset($value[0]['value'])) {
        $result = $value[0]['value'];
      }
    }
    return $result;
  }

  /**
   * @return string
   */
  public static function generateToken()
  {
    $token = bin2hex(Crypt::randomBytes(20));
    return $token;
  }

  public static function getTemplates(
    $module = 'small_messages',
    $template_names = []
  ) {
    $templates = [];

    // Default Names
    $default_directory = 'templates';
    $default_root_type = 'module';
    $default_module_name = $module;
    $module_name_url = str_replace('_', '-', $module);
    $default_template_prefix = $module_name_url . '-';
    $default_template_suffix = '.html.twig';

    // Get Config
    $config = \Drupal::config($module . '.settings');

    // Load Path Module from Settings
    $config_root_type = $config->get('get_path_type');
    $config_module_name = $config->get('get_path_name');

    foreach ($template_names as $template_name) {
      // change "_" with "-"
      $template_name_url = str_replace('_', '-', $template_name);

      // Default
      $root_type = $default_root_type;
      $module_name = $default_module_name;
      $template_full_name =
        '/' .
        $default_directory .
        '/' .
        $default_template_prefix .
        $template_name_url .
        $default_template_suffix;

      // If Path Module is set
      if ($config_root_type && $config_module_name) {
        $root_type = $config_root_type;
        $module_name = $config_module_name;

        // If Template Name is set
        $config_template_name = $config->get('template_' . $template_name);
        if ($config_template_name) {
          $template_full_name = $config_template_name;
        }
      }

      $template_path =
        drupal_get_path($root_type, $module_name) . $template_full_name;

      // output
      $templates[$template_name] = $template_path;
    }

    return $templates;
  }

  /**
   * @param NodeInterface | Node $node
   * @param string $field_name
   * @return boolean | string | array
   */
  public static function getAudioFieldValue($node, $field_name)
  {
    $result = [];

    $field_name = 'field_' . $field_name;

    $mid = 0; // Media ID
    $tid = 0; // Audio Term ID
    $url = ''; // url to audiofile
    $name = ''; // Name / Title of Audiofile
    $file_name = '';
    $mime_type = '';

    if (!$node->get($field_name)->isEmpty()) {
      // Media
      $media_entity = $node->get($field_name)->entity;
      $name = $media_entity->label();
      $mid = $media_entity->id();

      // Media -> Audio
      $media_field = $media_entity
        ->get('field_media_audio_file')
        ->first()
        ->getValue();
      $tid = $media_field['target_id'];

      // Media -> Audio -> File
      if ($tid) {
        $file = File::load($tid);
        if ($file) {
          $file_name = $file->getFilename();
          $uri = $file->getFileUri();
          $url = file_create_url($uri);
          $mime_type = $file->getMimeType();
        }

        $result = [
          'mid' => $mid,
          'tid' => $tid,
          'media_link' => $url,
          'mime_type' => $mime_type,
          'name' => $name,
          'file_name' => $file_name,
        ];
      }
    }

    return $result;
  }

  public static function createImageStyle(
    $img_id_or_file,
    $image_style_id,
    $dont_create = false
  ) {
    $image = [];
    $image_style = ImageStyle::load($image_style_id);

    if ($img_id_or_file && $img_id_or_file instanceof FileInterface) {
      $file = $img_id_or_file;
    } else {
      $file = File::load($img_id_or_file);
    }

    if ($file && $image_style) {
      $file_image = \Drupal::service('image.factory')->get($file->getFileUri());
      /** @var \Drupal\Core\Image\Image $image */

      if ($file_image->isValid()) {
        $image_uri = $file->getFileUri();
        $destination = $image_style->buildUrl($image_uri);

        if (!file_exists($destination)) {
          if (!$dont_create) {
            $image_style->createDerivative($image_uri, $destination);
          }
        }

        $file_size = filesize($image_uri);
        $file_size_formatted = format_size($file_size);
        list($width, $height) = getimagesize($image_uri);

        $image['url'] = $destination;
        $image['uri'] = $image_uri;
        $image['file_size'] = $file_size;
        $image['file_size_formatted'] = $file_size_formatted;
        $image['width'] = $width;
        $image['height'] = $height;
      }
    }
    return $image;
  }
}

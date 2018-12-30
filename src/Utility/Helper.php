<?php

namespace Drupal\small_messages\Utility;

use Drupal\Component\Utility\Crypt;
use Drupal\node\Entity\Node;

class Helper
{

    public static function getTermsByID($vid)
    {
        $term_list = [];
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        foreach ($terms as $term) {
            $term_list[$term->tid] = $term->name;
        }
        return $term_list;
    }

    public static function getTermsByName($vid)
    {
        $term_list = [];
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        foreach ($terms as $term) {
            $term_list[$term->name] = $term->id;
        }
        return $term_list;
    }

    /**
     * @param Node $node
     * @param string $field_name
     * @param null $term_list
     * @param bool $force_array
     * @return boolean | string | array
     */
    public static function getFieldValue(Node $node, $field_name, $term_list = null, $force_array = false)
    {
        $field_name = 'field_' . $field_name;
        $result = false;
        if ($node->get($field_name)) {
            $value = $node->get($field_name)->getValue();

            // single
            if (count($value) == 1) {

                // Standart Field
                if ($value && $value[0] && isset($value[0]['value'])) {
                    $result = $value[0]['value'];
                }

                // Target Field
                if ($value && $value[0] && isset($value[0]['target_id'])) {
                    $result = $value[0]['target_id'];
                }

                // Value is Taxonomy Term
                if ($term_list) {
                    $result = $term_list[$result];
                }

                if ($force_array) {
                    $arr[] = $result;
                    $result = $arr;
                }
            }

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
        }


        return $result;
    }

    public static function getToken($node_or_node_id)
    {
        $field_name = 'field_smmg_token';
        $result = false;

        if (is_numeric($node_or_node_id)) {
            $entity = Node::load( $node_or_node_id);
        }
        else{
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
}
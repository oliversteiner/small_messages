<?php

namespace Drupal\small_messages\Utility;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\small_messages\Exceptions\SmmgHelperException;
use Drupal\taxonomy\Entity\Term;
use Exception;

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
     * @param $name
     * @return bool|number
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public static function getOrigin($name)
    {

        $vid = 'smmg_origin';
        $tid = false;

        $term_list = [];
        $term_names = [];

        // Load Origin Terms
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        foreach ($terms as $term) {
            $term_list[$term->name] = $term->id;
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
    public static function getFieldValue( $node, $field_name, $term_list = null, $force_array = false)
    {
        $result = false;



        try {

            // check for 'field_field_NAME'
            $pos = strpos($field_name, 'field_');

            if ($pos === 0) {

                throw new Exception('Use $field_name without "field_" in HELPER:getFieldValue(' . $field_name.')');
            }

            $field_name = 'field_' . $field_name;

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
        }
        catch
            (Exception $e) {

                    throw $e;

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

    public static function getTemplates($module = "small_messages", $template_names = [])
    {
        $templates = [];


        // Default Names
        $default_directory = "templates";
        $default_root_type = "module";
        $default_module_name = $module;
        $module_name_url = str_replace('_', '-', $module);
        $default_template_prefix = $module_name_url . "-";
        $default_template_suffix = ".html.twig";


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
            $template_full_name = '/' . $default_directory . '/' . $default_template_prefix . $template_name_url . $default_template_suffix;

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

            $template_path = drupal_get_path($root_type, $module_name) . $template_full_name;

            // output
            $templates[$template_name] = $template_path;
        }


        return $templates;
    }
}
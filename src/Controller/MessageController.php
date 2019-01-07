<?php

namespace Drupal\small_messages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\small_messages\Utility\SendInquiryTemplateTrait;

/**
 * Class MessageController.
 */
class MessageController extends ControllerBase
{


    use SendInquiryTemplateTrait;

    /**
     * {@inheritdoc}
     */
    protected function getModuleName()
    {
        return 'small_messages';
    }

    public static function generateMessagePlain($text, $search_keys, $placeholders, $template_nid)
    {
        {

            // load Design Template
            $entity = \Drupal::entityTypeManager()
                ->getStorage('node');


            $design_template_content = $entity->get('field_smmg_template_plain_text')
                ->getValue();
            $design_template = $design_template_content[0]['value'];

            // insert Message in to Design Template
            $template_with_message = str_replace('@@_text_@@', $text, $design_template);
            $body_content = $template_with_message;

            // Replace all Placeholders with Values
            foreach ($search_keys as $index => $search_key) {
                $replace = $placeholders[$index];
                $body_content = str_replace($search_key, $replace, $body_content);
            }

            // Output
            return $body_content;
        }

    }

    public static function generateMessageHtml($message, $search_keys, $placeholders, $template_nid, $body_only = FALSE)
    {

        // load Design Template
        $entity = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->load($template_nid);

        $template_html_head = '';
        $e_template_html_head = $entity->get('field_smmg_template_html_head')
            ->getValue();
        if (!empty($e_template_html_head)) {
            $template_html_head = $e_template_html_head[0]['value'];
        }

        $template_html_body = $entity->get('field_smmg_template_html_body')
            ->getValue();
        $template_html_body = $template_html_body[0]['value'];


        // Desfine the HTML -Parts
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> ';
        $html_start = '<html xmlns="http://www.w3.org/1999/xhtml">';
        $head = '<head>' . $template_html_head . '</head>';
        $body_start = '<body>';
        $body_content = '';
        $body_end = '</body>';
        $html_end = '</html>';

        // insert Message in to Design Template
        $template_with_message = str_replace('@@_text_@@', $message, $template_html_body);
        $body_content = $template_with_message;

        // Replace all Placeholders with Values
        foreach ($search_keys as $index => $search_key) {


            $replace = $placeholders[$index];


            $body_content = str_replace($search_key, $replace, $body_content);
        }

        if (FALSE === $body_only) {

            // assemble all HTMl - Parts
            $html_file = $doctype . $html_start . $head . $body_start . $body_content . $body_end . $html_end;
        } else {
            $html_file = $body_content;

        }
        // Output
        return $html_file;
    }

    public function startRun($message_nid)
    {

        // Message

        // load Message
        $entity = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->load($message_nid);

        // Title
        $message['id'] = $entity->id();
        $message['title'] = $entity->label();

        $plaintext = $entity->get('body')->getValue();
        $message['plaintext'] = $plaintext[0]['value'];

        $htmltext = $entity->get('body')->getValue();
        $message['htmltext'] = $htmltext[0]['value'];

        // subscribers
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
        $output['message'] = $message;

        // Adresses

        // load Groups
        $entity_groups = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadMultiple($subscriber);

        $list = [];
        $unique_list = [];

        $list_index = 0;

        // Proceed Groups
        foreach ($entity_groups as $group) {

            $term_id = $group->id();

            // get all
            $node_subscripters = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadByProperties([
                    'type' => 'goenner',
                    'field_smmg_subscriber_group' => $term_id,
                ]);

            foreach ($node_subscripters as $entity) {

                $id = $entity->id();

                if (in_array($id, $unique_list)) {
                    // übersprichen, da schon erfasse

                } // neue ID:
                else {

                    $unique_list[] = $id;

                    // email
                    $email = [];
                    if (!empty($entity->field_email)) {
                        $email = $entity->get('field_email')
                            ->getValue();
                        $email = $email[0]['value'];

                    }

                    // first_name
                    $first_name = [];
                    if (!empty($entity->field_first_name)) {
                        $first_name = $entity->get('field_first_name')
                            ->getValue();
                        $first_name = $first_name[0]['value'];

                    }

                    // last_name
                    $last_name = [];
                    if (!empty($entity->field_last_name)) {
                        $last_name = $entity->get('field_last_name')
                            ->getValue();
                        $last_name = $last_name[0]['value'];
                    }


                    $list[$list_index]['id'] = $id;
                    $list[$list_index]['titel'] = $entity->label();
                    $list[$list_index]['last_name'] = $last_name;
                    $list[$list_index]['first_name'] = $first_name;
                    $list[$list_index]['email'] = $email;

                    $list_index++;
                } // else
            } // foreach

            $group_index++;
        }

        $output['addresses'] = $list;
        $output['unique'] = $unique_list;


        foreach ($output['addresses'] as $address) {

            $data['title'] = $output['message']['title'];
            $data['message'] = $output['message']['plaintext'];
            $data['htmltext'] = $output['message']['htmltext'];
            $data['from'] = "newsletter@drullo.ch";
            $data['to'] = $address['email'];

            self::sendmail($data);

        }

        return $output;
    }

    /**
     * @return array
     *
     */
    public function sendMessage()
    {


        self::sendmail();


        return [
            '#type' => 'markup',
            '#markup' => $this->t('sendMessage:'),
        ];
    }

    /**
     * @return mixed
     */
    public function sandboxPage()
    {

        $result = $this->startRun(1191);


        // Form mit test


        $form['list'] = [
            '#markup' => '<p>Sandbox</p>' .
                '<hr>' .
                '<div class="sandbox"><pre>' . $result['message']['title'] . '</pre></div>' .

                '<hr>',
        ];
        $form['sortable'] = [
            '#theme' => 'item_list',
            '#items' => $result['unique'],
            '#attributes' => ['id' => 'sortable'],
        ];

        return $form;
    }

    function sendmail($data)
    {


        $params['title'] = $data['title'];
        $params['message'] = $data['message'];
        $params['htmltext'] = $data['htmltext'];
        $params['from'] = $data['from'];
        $to = $data['to'];


        // System
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'small_messages';
        $key = 'EMAIL_SMTP'; // Replace with Your key
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;


        // Send
        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
        if ($result['result'] != TRUE) {

            $message = t('There was a problem sending your email notification to @email.', ['@email' => $to]);
            drupal_set_message($message, 'error');
            \Drupal::logger('mail-log')->error($message);
            return;
        } else {
            $message = t('An email notification has been sent to @email.', ['@email' => $to]);
            drupal_set_message($message);
            \Drupal::logger('mail-log')->notice($message);

        }

    }

    public function adminTemplateAction()
    {
    }
}

<?php

  namespace Drupal\small_messages\Controller;

  use Drupal\Core\Controller\ControllerBase;
  use Drupal\small_messages\Utility\SendInquiryTemplateTrait;

  /**
   * Class MessageController.
   */
  class MessageController extends ControllerBase {


    use SendInquiryTemplateTrait;

    public static function generateMessagePlain($text, $search_keys, $placeholders, $template_nid) {
      {

        // load Design Template
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($template_nid);


        $design_template_content = $entity->get('field_smmg_template_plaint')
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

    public static function generateMessageHtml($message, $search_keys, $placeholders, $template_nid, $body_only = FALSE) {

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
      $head = '<head>'.$template_html_head.'</head>';
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
        $html_file = $doctype.$html_start.$head.$body_start.$body_content.$body_end.$html_end;
      }
      else {
        $html_file = $body_content;

      }
      // Output
      return $html_file;
    }


    /**
     * {@inheritdoc}
     */
    protected function getModuleName() {
      return 'small_messages';
    }

    /**
     * @param      $message_nid
     * @param bool $test
     *
     * @return mixed
     *
     *
     */
    protected static function startRun($message_nid, $test = FALSE) {


      // Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($message_nid);

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


      $group_index = 0;

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
            'field_smmg_subscriber_tags' => $term_id,
          ]);

        foreach ($node_subscripters as $entity) {

          $subscripter_nid = $entity->id();

          if (in_array($subscripter_nid, $unique_list)) {
            // übersprichen, da schon erfasset

            // dpm('die ID '.$id.' ist doppelt.');
          }
          // neue ID:
          else {

            $unique_list[] = $subscripter_nid;

            // accept_newsletter
            $accept_newsletter = 0;
            if (!empty($entity->field_smmg_accept_newsletter)) {
              $e_accept_newsletter = $entity->get('field_smmg_accept_newsletter')
                ->getValue();

              if (!empty($e_accept_newsletter)) {
                $accept_newsletter = $e_accept_newsletter['value'];
              }
            }

            // email
            $email = '';
            if (!empty($entity->field_e_mail)) {
              $e_email = $entity->get('field_e_mail')
                ->getValue();
              if (!empty($e_email)) {
                $email = $e_email[0]['value'];
              }
            }

            // vorname
            $vorname = '';
            if (!empty($entity->field_vorname)) {
              $e_vorname = $entity->get('field_vorname')
                ->getValue();
              if (!empty($e_vorname)) {
                $vorname = $e_vorname[0]['value'];
              }


            }

            // nachname
            $nachname = '';
            if (!empty($entity->field_nachname)) {
              $e_nachname = $entity->get('field_nachname')
                ->getValue();
              if (!empty($e_nachname)) {
                $nachname = $e_nachname[0]['value'];
              }
            }


            $list_named[$list_index]['id'] = $subscripter_nid;
            $list_named[$list_index]['titel'] = $entity->label();
            $list_named[$list_index]['vorname'] = $vorname;
            $list_named[$list_index]['nachname'] = $nachname;
            $list_named[$list_index]['email'] = $email;
            $list_named[$list_index]['accept_newsletter'] = $accept_newsletter;

            $list[$list_index][0] = $subscripter_nid;
            $list[$list_index][1] = $entity->label();
            $list[$list_index][2] = $vorname;
            $list[$list_index][3] = $nachname;
            $list[$list_index][4] = $email;
            $list[$list_index][5] = $accept_newsletter;

            $list_index++;
          } // else

        } // foreach

        $group_index++;
      }

      $output['addresses'] = $list_named;
      $output['placeholders'] = $list;
      $output['unique'] = $unique_list;

      $search_keys = [
        '@@_id_@@',
        '@@_titel_@@',
        '@@_vorname_@@',
        '@@_nachname_@@',
        '@@_email_@@',
        '@@_accept_@@',
      ];



      foreach ($list as $subscriber) {

        $placeholders = $subscriber;


        $text_plain = MessageController::generateMessagePlain($text, $search_keys, $placeholders, $design_template_id);
        $text_html = MessageController::generateMessageHtml($text, $search_keys, $placeholders, $design_template_id);


        if ($test) {

          dpm('[test] send to - ' . $subscriber[4]);
        }
        else {
          $data['title'] = $title;
          $data['message'] = $text_plain;
          $data['htmltext'] = $text_html;
          $data['from'] = "newsletter@konzert-um-3.ch";
          $data['to'] = $subscriber[4];

          if(!empty($subscriber[4])){


               self::sendmail($data);
          }


        }


      }

      return $output;
    }

    /**
     * @return array
     *
     */
    public function testSendMessage() {

      $message_id =1201;

      self::startRun($message_id, TRUE);

/*
      return [
        '#type' => 'markup',
        '#markup' => $this->t('sendMessage:'),
      ];*/


    }

    /**
     * @return array
     *
     */
    public function sendMessage($message_id) {


      $result = self::startRun($message_id);


      return [
        '#type' => 'markup',
        '#markup' => $this->t('sendMessage'),
      ];


    }

    /**
     * @return mixed
     */
    public function sandboxPage() {

      $result = $this->startRun(1191);


    //  kint($result);

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


    /**
     * @param $data
     *
     */
     static function sendmail($data) {


      $params['title'] = $data['title'];
      $params['message'] = $data['message'];
      $params['htmltext'] = $data['htmltext'];
      $params['from'] = $data['from'];
      $to = $data['to'];


      // System
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'small_messages';
      $key = 'HTML'; // Replace with Your key
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = true;


      // Send
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

      if ((@$result['result'] != TRUE)) {

        $message = t('There was a problem sending your email notification to @email.', ['@email' => $to]);
        drupal_set_message($message, 'error');
        \Drupal::logger('mail-log')->error($message);
        return;
      }
      else {
        $message = t('An email notification has been sent to @email.', ['@email' => $to]);
       // drupal_set_message($message);
        \Drupal::logger('mail-log')->notice($message);

      }

    }


    public function adminTemplateAction() {
    }
  }

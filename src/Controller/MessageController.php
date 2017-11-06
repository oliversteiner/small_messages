<?php

  namespace Drupal\small_messages\Controller;

  use Drupal\Core\Controller\ControllerBase;
  use Drupal\small_messages\Utility\SendInquiryTemplateTrait;

  /**
   * Class MessageController.
   */
  class MessageController extends ControllerBase {


    use SendInquiryTemplateTrait;

    public  function generateMessagePlain($message, $search_keys, $placeholders) {
      {
        // load Design Template
        $entity = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($message['template']);
        $design_template = $entity->get('field_smmg_template_plain')->getValue();

        // insert Message in to Design Template
        $template_with_message = str_replace('@@_text_@@', $message, $design_template);
        $body_content = '';

        // Replace all Placeholders with Values
        foreach ($search_keys as $index => $search_key) {
          $replace = $placeholders[$index];
          $body_content = str_replace($search_key, $replace, $template_with_message);
        }

        // Output
        return $body_content;
      }

    }

    public  function generateMessageHtml($message, $search_keys, $placeholders) {

      // load Design Template
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($message['template']);
      $design_template = $entity->get('field_smmg_template_html')->getValue();

      // Desfine the HTML -Parts
      $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> ';
      $html_start = '<html xmlns="http://www.w3.org/1999/xhtml">';
      $head = '<head>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->
    <title>Newsletter</title>
    <!-- Schriften IE -->
    <!--[if !mso]><!-- -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet" type="text/css">
    <!--<![endif]-->
    <style type="text/css" id="media-query">
        body {
            margin: 0;
            padding: 0; }
        table, tr, td {
            vertical-align: top;
            border-collapse: collapse; }
        * { line-height: inherit; }
        /* Apple */
        a[x-apple-data-detectors=true] {
            color: inherit !important;
            text-decoration: none !important; }
    </style>
      </head>';
      $body_start = '<body>';
      $body_content = '';  // here comes the text
      $body_end = '</body>';
      $html_end = '</html>';

      // insert Message in to Design Template
      $template_with_message = str_replace('@@_text_@@', $message, $design_template);

      // Replace all Placeholders with Values
      foreach ($search_keys as $index => $search_key) {
        $replace = $placeholders[$index];
        $body_content = str_replace($search_key, $replace, $template_with_message);
      }

      // assemble all HTMl - Parts
      $html_file = $doctype.$html_start . $head . $body_start . $body_content . $body_end . $html_end;

      // Output
      return $html_file;
    }


    /**
     * {@inheritdoc}
     */
    protected function getModuleName() {
      return 'small_messages';
    }


    protected function startRun($message_nid, $test = FALSE) {

      // Message

      // load Message
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($message_nid);

      // Title
      $message['id'] = $entity->id();
      $message['title'] = $entity->label();

      $body = $entity->get('body')->getValue();
      $message['body'] = $body[0]['value'];

      $design_template_id = $entity->get('field_smmg_design_template')
        ->getValue();
      $message['template'] = $design_template_id[0]['target_id'];


      // subscribers
      $subscriber = [];
      if (!empty($entity->field_newsletter_mailto_groups)) {

        // Load all items
        $subscriber_groups_items = $entity->get('field_newsletter_mailto_groups')
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
            'field_empfaenger_gruppe' => $term_id,
            // 'field_newsletter' => 1 // TODO activate Field Newsletter
          ]);

        foreach ($node_subscripters as $entity) {

          $id = $entity->id();

          if (in_array($id, $unique_list)) {
            // übersprichen, da schon erfasse

            // dpm('die ID '.$id.' ist doppelt.');
          }
          // neue ID:
          else {

            $unique_list[] = $id;

            // email
            $email = [];
            if (!empty($entity->field_e_mail)) {
              $email = $entity->get('field_e_mail')
                ->getValue();
              $email = $email[0]['value'];

            }

            // vorname
            $vorname = [];
            if (!empty($entity->field_vorname)) {
              $vorname = $entity->get('field_vorname')
                ->getValue();
              $vorname = $vorname[0]['value'];

            }

            // nachname
            $nachname = [];
            if (!empty($entity->field_nachname)) {
              $nachname = $entity->get('field_nachname')
                ->getValue();
              $nachname = $nachname[0]['value'];
            }


            $list[$list_index]['id'] = $id;
            $list[$list_index]['titel'] = $entity->label();
            $list[$list_index]['vorname'] = $vorname;
            $list[$list_index]['nachname'] = $nachname;
            $list[$list_index]['email'] = $email;

            $list_index++;
          } // else
        } // foreach

        $group_index++;
      }

      $output['addresses'] = $list;
      $output['unique'] = $unique_list;

      $search_keys = [
        '@@_id_@@',
        '@@_titel_@@',
        '@@_vorname_@@',
        '@@_nachname_@@',
        '@@_email_@@',
      ];

      $message = $output['body'];

      if ($test) {
        // generiere mit dem ersten Datensatz die email:
        $placeholders = $output['addresses'][0];

        // Links zur Vorschau
        $url_mail_plaintext = 'smmg/preview_message/plain/' . $message_nid . '/' . $search_keys . '/' . $placeholders;
        $url_mail_html = 'smmg/preview_plaintext/html/' . $message_nid . '/' . $search_keys . '/' . $placeholders;

        // Output Drupal Message
        $d_message = '';
        $d_message .= 'generated Email:<strong>' . $output['message']['title'] . '</strong> ';
        $d_message .= 'Emailbody:';
        $d_message .= '[<a href="' . $url_mail_plaintext . '">Plaintext</a>]';
        $d_message .= '[<a href="' . $url_mail_html . '">HTML</a>]';

        drupal_set_message($d_message);
      }

      foreach ($output['addresses'] as $address) {

        $placeholders = $address;

        $body_plain = $this->generateMessagePlain($message, $search_keys, $placeholders);
        $body_html = $this->generateMessageHtml($message, $search_keys, $placeholders);


        if ($test) {

          $d_message = '';
          $d_message = '[test] ' . $address['email'];
          drupal_set_message($d_message);


        }
        else {
          $data['title'] = $output['message']['title'];
          $data['message'] = $body_plain;
          $data['htmltext'] = $body_html;
          $data['from'] = "newsletter@konzert-um-3.ch";
          $data['to'] = $address['email'];

          self::_sendmail($data);

        }


      }

      return $output;
    }

    /**
     * @return array
     *
     */
    public function testSendMessage($message_id) {


      self::startRun($message_id, TRUE);


      return [
        '#type' => 'markup',
        '#markup' => $this->t('sendMessage:'),
      ];
    }

    /**
     * @return array
     *
     */
    public function sendMessage($message_id) {


      self::startRun($message_id);


      return [
        '#type' => 'markup',
        '#markup' => $this->t('sendMessage:'),
      ];
    }

    /**
     * @return mixed
     */
    public function sandboxPage() {

      $result = $this->startRun(1191);


      kint($result);

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

    private static function _sendmail($data) {


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
      $send = TRUE;


      // Send
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
      if ($result['result'] != TRUE) {

        $message = t('There was a problem sending your email notification to @email.', ['@email' => $to]);
        drupal_set_message($message, 'error');
        \Drupal::logger('mail-log')->error($message);
        return;
      }
      else {
        $message = t('An email notification has been sent to @email ', ['@email' => $to]);
        drupal_set_message($message);
        \Drupal::logger('mail-log')->notice($message);

      }

    }


    public function adminTemplateAction() {
    }
  }

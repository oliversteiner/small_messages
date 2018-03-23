<?php

  namespace Drupal\small_messages\Controller;

  use Drupal\Core\Controller\ControllerBase;
  use Drupal\small_messages\Utility\SendInquiryTemplateTrait;

  /**
   * Class MessageController.
   */
  class MessageController extends ControllerBase {


    use SendInquiryTemplateTrait;

    /**
     * {@inheritdoc}
     */
    protected function getModuleName() {
      return 'small_messages';
    }


    public function startRun($message_nid) {

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
      $message['htmltext']= $htmltext[0]['value'];

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
            //   'field_newsletter' => 1
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
            $list[$list_index]['nachname'] = $nachname;
            $list[$list_index]['vorname'] = $vorname;
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
        $data['htmltext'] =$output['message']['htmltext'];
        $data['from'] = "newsletter@konzert-um-3.ch";
        $data['to'] =$address['email'];

        self::sendmail($data);

      }

      return $output;
    }

    /**
     * @return array
     *
     */
    public function sendMessage() {


      self::sendmail();


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

    function sendmail($data) {


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


      // Inhalt
      /*      $params['title'] = 'Email Titel Test';
            $params['message'] = '5 Plaintext test';
            $params['htmltext'] = '<text style="color:#4ea3ff; font-size:50px;">3 HTML Text Test</text>';
            $params['from'] = 'newsletter@konzert-um-3.ch';*/


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

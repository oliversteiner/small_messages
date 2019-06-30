<?php

namespace Drupal\small_messages\Utility;

use Drupal\Core\Link;

class Email
{
  /**
   * @param $modul
   * @param $email
   */
  static function sendmail($modul, $email)
  {
    // Debug
    $send = true;

    // Text
    $params['title'] = $data['title'];
    $params['message_plain'] = $data['message_html'];
    $params['message_html'] = $data['message_html'];

    // Addresses
    $params['from'] = $data['from'];
    $to = $data['to'];

    // System
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = $modul;
    $key = 'EMAIL_SMTP';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    // Send mail
    $result = $mailManager->mail(
      $module,
      $key,
      $to,
      $langcode,
      $params,
      null,
      $send
    );

    // Result
    if ($result && isset($result['result']) && $result['result'] != true) {
      $message = t(
        'There was a problem sending your email notification to @email.',
        ['@email' => $to]
      );
      \Drupal::messenger()->addMessage($message, 'error');
      \Drupal::logger('mail-log')->error($message);
    } else {
      $message = t('An email notification has been sent to @email.', [
        '@email' => $to,
      ]);
      \Drupal::messenger()->addMessage($message);
      \Drupal::logger('mail-log')->notice($message);
    }
  }


  /**
   * @param $module
   * @param $data
   * @param $templates
   * @return bool
   */
  public static function sendNotificationMail($module, $data, $templates): bool
  {
    $config_email_test = self::getConfigEmailTest($module);

    // Build Emailadresses
    $config_email_addresses = self::getEmailAddressesFromConfig($module);

    // Data
    $first_name = $data['address']['first_name'];
    $last_name = $data['address']['last_name'];
    $email_subscriber = $data['address']['email'];
    $module = $data['module'];

    $email_title = empty($data['title'])
      ? "$module - $first_name $last_name"
      : $data['title'];

    // HTML
    $template_html = file_get_contents($templates['email_html']);
    $build_html = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template_html,
        '#context' => $data,
      ],
    ];

    // Render Twig Template
    $message_html_body = \Drupal::service('renderer')->render($build_html);

    // Plain
    $template_plain = file_get_contents($templates['email_plain']);
    $build_plain = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template_plain,
        '#context' => $data,
      ],
    ];

    // Get Email Addresses
    $email_address_from = $config_email_addresses['from'];
    $email_addresses_to = $config_email_addresses['to'];

    // Testmode - Dont send email to Subscriber if "test mode" is checked on settings page.
    if ($config_email_test == 1) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'), $module . '.settings'
      )->toString();
      \Drupal::messenger()->addWarning(
        t(
          'Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
          array('@link' => $link)
        )
      );
    } else {
      // Add Subscriber email to email addresses
      $email_addresses_to[] = $email_subscriber;
    }

    foreach ($email_addresses_to as $email_address_to) {
      $message_html = self::replacePlaceholderInText(
        $message_html_body,
        $data['address']
      );

      $data['title'] = $email_title;
      $data['message_plain'] = $build_plain;
      $data['message_html'] = $message_html;
      $data['from'] = $email_address_from;
      $data['to'] = $email_address_to;

      self::sendmail($module, $email);
    }

    return true;
  }

  /**
   * @param $module
   * @param $data
   * @param $templates
   * @return bool
   */
  public static function sendCouponMail($module, $data, $templates)
  {
    $config_email_test = self::getConfigEmailTest($module);

    // Build Emailadresses
    $config_email_addresses = self::getEmailAddressesFromConfig($module);

    // Data
    $first_name = $data['address']['first_name'];
    $last_name = $data['address']['last_name'];
    $email_subscriber = $data['address']['email'];
    $module = $data['module'];

    $email_title = empty($data['title'])
      ? "$module - $first_name $last_name"
      : $data['title'];

    // HTML
    $template_html = file_get_contents($templates['email_html']);
    $build_html = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template_html,
        '#context' => $data,
      ],
    ];

    // Render Twig Template
    $message_html_body = \Drupal::service('renderer')->render($build_html);

    // Plain
    $template_plain = file_get_contents($templates['email_plain']);
    $build_plain = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template_plain,
        '#context' => $data,
      ],
    ];

    // Get Email Addresses
    $email_address_from = $config_email_addresses['from'];
    $email_addresses_to = $config_email_addresses['to'];

    // Testmode - Dont send email to Subscriber if "test mode" is checked on settings page.
    if ($config_email_test == 1) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'), $module . '.settings'
      )->toString();
      \Drupal::messenger()->addWarning(
        t(
          'Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
          array('@link' => $link)
        )
      );

      $data['message_plain'] = $build_plain;
      $data['message_html'] = $message_html_body;
      self::showEmail($module, $data);

    } else {
      // Add Subscriber email to email addresses
      $email_addresses_to[] = $email_subscriber;
    }

    foreach ($email_addresses_to as $email_address_to) {
      $message_html = self::replacePlaceholderInText(
        $message_html_body,
        $data['address']
      );

      $data['title'] = $email_title;
      $data['message_plain'] = $build_plain;
      $data['message_html'] = $message_html;
      $data['from'] = $email_address_from;
      $data['to'] = $email_address_to;

      self::sendmail($module, $email);
    }

    return true;
  }


  /**
   * @param $module
   * @param $data
   * @param $templates
   * @return bool
   */
  public static function sendNewsletterMail($module, $data): bool
  {
    // Build Settings Name
    $config_email_test = self::getConfigEmailTest($module);

    // Testmode - Dont send email to Subscriber if "test mode" is checked on settings page.
    if ($config_email_test === 1) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'),
        $module . '.settings'
      )->toString();
      \Drupal::messenger()->addWarning(
        t(
          $data['to'] .
          ' - Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
          array('@link' => $link)
        )
      );
    } else {
      self::sendmail($module, $data);
    }

    return true;
  }


  /**
   * @param $modul
   * @param $data
   * @return array
   */
  public static function showEmail($module, $data)
  {

    $build = [
      '#markup' =>
        $data['message_plain'] . '<br><hr><br>' . $data['message_html'],
    ];
    return $build;
  }


  /**
   * @param $message
   * @param $data
   * @return mixed
   */
  public static function replacePlaceholderInText($message, $data)
  {
    foreach ($data as $key => $value) {
      $placeholder = '@_' . $key . '_@';
      $message = str_replace($placeholder, $value, $message);
    }
    return $message;
  }


  /**
   * @param $message
   * @param bool $body_only
   * @return string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function generateMessageHtml(
    $message,
    $body_only = false
  ): string
  {
    $text = $message['message_html'];
    $template_id = $message['template_nid'];

    // load Design Template
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($template_id);

    $template_html_head = '';
    $e_template_html_head = $entity
      ->get('field_smmg_template_html_head')
      ->getValue();
    if (!empty($e_template_html_head)) {
      $template_html_head = $e_template_html_head[0]['value'];
    }

    $template_html_body = $entity
      ->get('field_smmg_template_html_body')
      ->getValue();
    $template_html_body = $template_html_body[0]['value'];

    // Define the HTML-Parts
    $doctype =
      '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> ';
    $html_start = '<html xmlns="http://www.w3.org/1999/xhtml">';
    $head = '<head>' . $template_html_head . '</head>';
    $body_start = '<body>';
    $body_content = '';
    $body_end = '</body>';
    $html_end = '</html>';

    // insert Message in to Design Template
    $body_content = str_replace(
      '@_text_@',
      $text, $template_html_body

    );

    // return only HTML Body Content, No HEADER
    if ($body_only === true) {
      $html_file = $body_content;
    } else {
      // assemble all HTMl - Parts
      $html_file =
        $doctype .
        $html_start .
        $head .
        $body_start .
        $body_content .
        $body_end .
        $html_end;
    }
    // Output
    return $html_file;
  }


  /**
   * @param string $module
   * @return mixed
   */
  public static function getEmailAddressesFromConfig($module = 'small_messages')
  {
    $data['from'] = '';
    $data['to'] = [];

    $config = \Drupal::config($module . '.settings');

    $email_from = $config->get('email_from');

    $str_multiple_email_to = $config->get('email_to');

    $email_from = trim($email_from);
    $is_valid = \Drupal::service('email.validator')->isValid($email_from);

    if ($is_valid) {
      $data['from'] = $email_from;
    }

    $arr_email_to = explode(',', $str_multiple_email_to);

    foreach ($arr_email_to as $email_to) {
      $email_to = trim($email_to);
      $is_valid = \Drupal::service('email.validator')->isValid($email_to);
      if ($is_valid) {
        $data['to'][] = $email_to;
      }
    }

    return $email;
  }


  /**
   * @param $module
   * @return string
   */
  public static function getConfigEmailTest($module): string
  {
// Build Settings Name
    $module_settings_route = $module . '.settings';

    // Load Settings
    $config = \Drupal::config($module_settings_route);
    return $config->get('email_test');
  }
}

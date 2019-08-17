<?php

namespace Drupal\small_messages\Utility;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\small_messages\Controller\MessageController;

class Email
{
  /**
   * @param $module
   * @param $data
   * @param bool $notification
   */
  public static function sendmail($module, $data, $notification = true): void
  {
    // Debug
    $send = true;

    // remove Comments from HTML
    $message_html = self::removeCommentsFromHTML($data['message_html']); // remove Comments
    $message_html = (string)$message_html;
    $mail_from = $data['from'];
    $mail_to = $data['to'];

    // Text
    $params['title'] = $data['title'];
    $params['message_plain'] = (string)$data['message_plain'];
    $params['message_html'] = $message_html;

    // 'From' Addresses
    if (empty($mail_from)) {
      $mail_from = \Drupal::config('system.site')->get('mail');
    }

    // Check 'to'-Address
    if (is_array($mail_to)) {
      $message = t(
        '$data[\'to\'] cant be an array. Email is send only to first Item of the array.  Module:  @module.',
        ['@module' => $module]
      );
      Drupal::messenger()->addMessage($message, 'error');
      Drupal::logger('mail-log')->error($message);

      $mail_to = $data['to'][0];
    }

    // Addresses
    $params['from'] = $mail_from;
    $to = $mail_to;

    // System
    $mailManager = Drupal::service('plugin.manager.mail');
    $key = 'EMAIL_SMTP';
    $langcode = Drupal::currentUser()->getPreferredLangcode();

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
      Drupal::messenger()->addMessage($message, 'error');
      Drupal::logger('mail-log')->error($message);
    } else {
      $message = t(
        'An email notification has been sent to @email. Module was @module',
        [
          '@email' => $to,
          '@module' => $module,
        ]
      );
      // Show notification on Screen
      if ($notification) {
        Drupal::messenger()->addMessage($message);
      }
      Drupal::logger('mail-log')->notice($message);
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
    $message_html_body = Drupal::service('renderer')->render($build_html);

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
    if ($config_email_test) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'),
        $module . '.settings'
      )->toString();
      Drupal::messenger()->addWarning(
        t(
          $email_addresses_to[0] .
          ' - Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
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

      self::sendmail($module, $data);
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
      Drupal::messenger()->addWarning(
        t(
          $data['to'] .
          ' - Test mode active. No email was sent to the subscriber. Disable test mode on @link.',
          array('@link' => $link)
        )
      );
    } else {
      self::sendmail($module, $data, false);
    }

    return true;
  }

  /**
   * @param $module
   * @param $data
   * @return bool
   */
  public static function sendAdminMail($module, $data): bool
  {
    // Build Settings Name
    $config_email_test = self::getConfigEmailTest($module);
    $config_email_addresses = self::getEmailAddressesFromConfig($module);

    // Get Email Addresses
    if (empty($data['from'])) {
      $data['from'] = $config_email_addresses['from'];
    }
    if (empty($data['to'])) {
      $data['to'] = $config_email_addresses['to'][0];
    }

    // Testmode - Dont send email to Subscriber if "test mode" is checked on settings page.
    if ($config_email_test === 1) {
      // test mode active
      $link = Link::createFromRoute(
        t('Config Page'),
        $module . '.settings'
      )->toString();
      Drupal::messenger()->addWarning(
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

      if (empty($value)) {
        $value = '';
      }
      $message = str_replace($placeholder, $value, $message);
    }
    return $message;
  }

  /**
   * @param $message_nid
   * @param $member_nid
   * @param $text
   * @param $template_id
   * @param bool $body_only
   * @return string
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function generateMessageHtml(
    $message_nid,
    $member_nid,
    $text,
    $template_id,
    $body_only = false
  ): string
  {
    $base64 = MessageController::serializeTelemetry($message_nid, $member_nid);
    $route_parameters = ['base64' => $base64];
    $option = [
      'absolute' => true,
    ];
    $telemetry_gif_url = Url::fromRoute('small_messages.telemetry', $route_parameters, $option);
    $url = $telemetry_gif_url->toString();
    $telemetry_img = "<div><img src='$url' width='1px' height='1px' border='0' aria-label='zaehlpixel'></div>";

    // load Design Template
    $entity = Drupal::entityTypeManager()
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
    $body_content = str_replace('@_text_@', $text, $template_html_body);

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
        $telemetry_img .
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

    $config = Drupal::config($module . '.settings');

    $email_from = $config->get('email_from');

    $str_multiple_email_to = $config->get('email_to');

    $email_from = trim($email_from);
    $is_valid = Drupal::service('email.validator')->isValid($email_from);

    if ($is_valid) {
      $data['from'] = $email_from;
    }

    $arr_email_to = explode(',', $str_multiple_email_to);

    foreach ($arr_email_to as $email_to) {
      $email_to = trim($email_to);
      $is_valid = Drupal::service('email.validator')->isValid($email_to);
      if ($is_valid) {
        $data['to'][] = $email_to;
      }
    }

    return $data;
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
    $config = Drupal::config($module_settings_route);
    return $config->get('email_test');
  }

  /**
   * @param $html
   * @return string|string[]|null
   */
  public static function removeCommentsFromHTML($html)
  {
    return preg_replace('/<!--(.*)-->/Uis', '', $html);
  }

  /**
   * @param $email
   * @return string
   *
   * https://stackoverflow.com/questions/20545301/partially-hide-email-address-in-php/20545505
   */
  public static function obfuscate_email($email): string
  {
    $em = explode('@', $email);
    $name = implode('@', array_slice($em, 0, count($em) - 1));
    $len = floor(strlen($name) / 2);

    return substr($name, 0, $len) . str_repeat('*', $len) . '@' . end($em);
  }
}

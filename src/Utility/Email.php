<?php

namespace Drupal\small_messages\Utility;


use Drupal\Core\Link;

class Email
{
    public static function sendNotificationMail($module, $data, $templates)
    {

        // Build Settings Name
        $module_settings_route = $module.'.settings';

        // Load Settings
        $config = \Drupal::config($module_settings_route);
        $config_email_test = $config->get('email_test');

        // Build Emailadresses
        $config_email_addresses = self::getEmailAddressesFromConfig($module);

        // Data
        $first_name = $data['address']['first_name'];
        $last_name = $data['address']['last_name'];
        $email_subscriber = $data['address']['email'];

        $title = $data['title'];
        $email_title = "$title: $first_name $last_name";

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
            $link = Link::createFromRoute(t('Config Page'), $module_settings_route)->toString();
            \Drupal::messenger()->addWarning(t("Test mode active. No email was sent to the subscriber. Disable test mode on @link.", array('@link' => $link)));

        } else {
            // Add Subscriber email to email addresses
            $email_addresses_to[] = $email_subscriber;
        }

        foreach ($email_addresses_to as $email_address_to) {

            $message_html = self::generateMessageHtml($message_html_body);

            $data['title'] = $email_title;
            $data['message_plain'] = $build_plain;
            $data['message_html'] = $message_html;
            $data['from'] = $email_address_from;
            $data['to'] = $email_address_to;

             self::sendmail($data);

        }

        return true;

    }

    static function sendmail($data)
    {
        $params['title'] = $data['title'];
        $params['message_html'] = $data['message_html'];
        $params['message_plain'] = $data['message_html'];

        $params['from'] = $data['from'];
        $to = $data['to'];


        // System
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'smmg_coupon';
        $key = 'EMAIL_SMTP';
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;


        // Send
        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

        if ($result['result'] != TRUE) {

            $message = t('There was a problem sending your email notification to @email.', ['@email' => $to]);
            \Drupal::messenger()->addMessage($message, 'error');
            \Drupal::logger('mail-log')->error($message);
            return;
        } else {
            $message = t('An email notification has been sent to @email.', ['@email' => $to]);
            \Drupal::messenger()->addMessage($message);
            \Drupal::logger('mail-log')->notice($message);

        }

    }

    public static function generateMessageHtml($message)
    {

        // Build the HTML Parts
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $html_start = '<html xmlns="http://www.w3.org/1999/xhtml">';
        $head = '<head></head>';
        $body_start = '<body>';
        $body_content = $message;
        $body_end = '</body>';
        $html_end = '</html>';

        // assemble all HTMl Parts
        $html_file = $doctype . $html_start . $head . $body_start . $body_content . $body_end . $html_end;

        // HTML Output
        return $html_file;
    }

    public static function getEmailAddressesFromConfig($module = 'small_messages')
    {
        $email['from'] = '';
        $email['to'] = [];

        $config = \Drupal::config($module.'.settings');

        $email_from = $config->get('email_from');

        $str_multible_email_to = $config->get('email_to');

        $email_from = trim($email_from);
        $is_valid = \Drupal::service('email.validator')->isValid($email_from);

        if ($is_valid) {
            $email['from'] = $email_from;
        }


        $arr_email_to = explode(",", $str_multible_email_to);

        foreach ($arr_email_to as $email_to) {
            $email_to = trim($email_to);
            $is_valid = \Drupal::service('email.validator')->isValid($email_to);
            if ($is_valid) {
                $email['to'][] = $email_to;
            }

        }

        return $email;
    }
}

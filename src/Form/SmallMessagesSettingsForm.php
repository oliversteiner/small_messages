<?php

namespace Drupal\small_messages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SmallMessagesSettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'small_messages_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'small_messages.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Load Settings
        $config = $this->config('small_messages.settings');

        // load all Template Names


        // Fieldset General
        //
        // Fieldset Email
        //   - Email Address From
        //   - Email Address To
        //   - Email Test

        //

        // Fieldset General
        // -------------------------------------------------------------
        $form['general'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('General'),
            '#attributes' => ['class' => ['settings-general']],
        ];


        // Fieldset Email
        // -------------------------------------------------------------
        $form['email'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Email Settings'),
            '#attributes' => ['class' => ['email-settings']],
        ];

        // - Email From
        $form['email']['email_from'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email: From (admin@example.com)'),
            '#default_value' => $config->get('email_from'),
        );

        // - Email To
        $form['email']['email_to'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email: to (info@example.com)'),
            '#default_value' => $config->get('email_to'),
        );

        // - Email Test
        $form['email']['email_test'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Testmode: Don\'t send email to Subscriber'),
            '#default_value' => $config->get('email_test'),
        );





        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {


        // Retrieve the configuration
        $this->configFactory->getEditable('small_messages.settings')
            //
            //
            // Fieldset General
            // -------------------------------------------------------------
            // Fieldset Email
            // -------------------------------------------------------------
            // - Email From
            ->set('email_from', $form_state->getValue('email_from'))
            // - Email to
            ->set('email_to', $form_state->getValue('email_to'))
            // - Email Test
            ->set('email_test', $form_state->getValue('email_test'))
            //
            //
   
            ->save();

        //  Twig Templates
        // -------------------------------------------------------------
        $config = $this->configFactory->getEditable('small_messages.settings');
        
        $config->save();

        parent::submitForm($form, $form_state);
    }
}

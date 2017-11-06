<?php

namespace Drupal\small_messages\Utility;

/**
 *
 * @see \Drupal\Core\Render\Element\InlineTemplate
 * @see https://www.drupal.org/developing/api/8/localization
 */
trait MessagesTemplateTrait
{
    /**
     * {@inheritdoc}
     */

    /**
     * Generate a render array with our Admin content.
     *
     * @return array
     *   A render array.
     */
    public function messagesTemplate()
    {
        $template_path = $this->getMessagesTemplatePath();
        $template = file_get_contents($template_path);
        $build = [
            'description' => [
                '#type' => 'inline_template',
                '#template' => $template,
                '#context' => $this->getMessagesVariables(),
            ],
        ];
        return $build;
    }

    /**
     * Name of our module.
     *
     * @return string
     *   A module name.
     */
    abstract protected function getModuleName();

    /**
     * Variables to act as context to the twig template file.
     *
     * @return array
     *   Associative array that defines context for a template.
     */
    protected function getMessagesVariables()
    {
        $variables['module'] = $this->getModuleName();

        return $variables;
    }

    /**
     * Get full path to the template.
     *
     * @return string
     *   Path string.
     */
    protected function getMessagesTemplatePath()
    {
        return drupal_get_path('module', $this->getModuleName()) . "/templates/smmg-messages.html.twig";
    }

}

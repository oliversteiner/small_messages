# Small Messages
Drupal 8 Module

v 1.0.0

## TODO
- [ ] implement is_send
- [ ] Generate Template 'Default' on install
- [ ] Generate Rules on Install

## Requirements
- mailsystem
- smtp
- views
- views_admintools

## Recommended modules
- fontawesome

## Content Types
### Small Message
smmg_message
- smmg_message_text (the actual Message )
- smmg_message_is_template (Boolean: use this message as template )
- smmg_design_template (Entity Reference: Tid to smmg_template node)
- smmg_message_group ( Taxonomy Reference:  Categorize Messages)
- smmg_subscriber_group( taxonomy Reference: Subscribers Groups)
- smmg_send_date (timestamp)
- smmg_message_is_send (boolean)

### Small Message Template
smmg_template
- smmg_template_html_body
- smmg_template_html_head
- smmg_template_plain_text
- smmg_template_cover (Preview Image for fast recognition)

## Taxonomy
- smmg_message_group
- smmg_subscriber_group

## Roles
- smmg_member
- smmg_editor
- smmg_admin

## Install:

```
composer require drupal/mailsystem
composer require drupal/smtp
```


## Resources
HTML Email Templates
https://github.com/mailchimp/Email-Blueprints
https://github.com/w8tcha/CKEditor-CodeMirror-Plugin
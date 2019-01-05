# Small Messages
Drupal 8 Module

v 1.4


## Requirements
- mailsystem
- smtp
- auto_entitylabel

## Recommended modules
- fontawesome

## This Modules adds those 2 new Content Types:
### Messages
smmg_message
- smmg_message_text (the actual Message )
- smmg_message_template (Boolean: use this message as template )
- smmg_design_template (Entity Reference: Tid to smmg_template node)
- smmg_message_group ( Taxonomy Reference:  Categorize Messages)
- smmg_subscriber_group( taxonomy Reference: Subscribers Groups)
- smmg_send_date

### Small Message Template
smmg_template
- smmg_template_html_body
- smmg_template_html_head
- smmg_template_plain_text
- smmg_template_cover (Preview Image for fast recognition)

### Taxonomy
smmg_message_group
smmg_subscriber_group


## Install:

```
composer require drupal/mailsystem
composer require drupal/smtp
```
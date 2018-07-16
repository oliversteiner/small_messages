v 1.3

## Install:

substantial

```

composer require drupal/mailsystem
composer require drupal/smtp

drush cset system.mail interface.default SMTPMailSystem

```

Requirements
- mailsystem
- smtp

Recommended modules
- markdown



enable module

### Types:
Messages(smmg_message)
Messages Design Templates (smmg_message_design_template)


### Fields:

#### Type: Messages
(body) // use it for notes
smmg_text (the actual Message )
smmg_text_template (bool / use this node as text template? )
smmg_design_template (referenz to tid from  Node Messages Design Templates)
smmg_message_tags ( taxonomy / for categorize Messages)
smmg_subscriber_tags( taxonomy / send to this subscribers)
smmg_message_send_date

#### Type: Messages Design Templates
smmg_template_html_head
smmg_template_html_body
smmg_template_plaintext
smmg_template_cover (Preview Image for fast recognition)
smmg_template_files (for .html an .txt files)

### Taxonomy
smmg_message_tags
smmg_subscriber_tags

### Taxonomy Fild
ssmg_ignore_accept_nl


### Fields to add to the Subscribers
smmg_subscriber_tags
smmg_accept_newsletter




# German:
Files	    field_smmg_template_files	Datei
html body	field_smmg_template_html_body	Text (formatiert, lang)
html head	field_smmg_template_html_head	Klartext
Plaintext	field_smmg_template_plaintext	Klartext
Template Cover	field_smmg_template_cover	Image
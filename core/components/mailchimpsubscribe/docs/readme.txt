---------------------------------------
MailChimpSubscribe
---------------------------------------
Version: 1.0.0-pl
Author: Sterc <modx@sterc.nl>
---------------------------------------

MailChimpSubscribe

Package for subscribing users in Mailchimp lists using FormIt.
Adds a snippet for retrieving Mailchimp lists in a single select TV and a
FormIt hook for subscribing Mailchimp users to the list provided in the pages Template Variable.

Functionalities

* FormIt hook for subscribing users to Mailchimp lists based on TV value
* MailChimpGetLists: Snippet for creating a select list of MailChimp lists.

How to get set up

* Add MailChimp API Key in systemsettings: mailchimpsubscribe.mailchimp_api_key
* Create a new single select TV variable and set the input option values to:
    @EVAL return $modx->runSnippet('MailChimpGetLists');
* Add MailChimp List ID TV in systemsettings: mailchimpsubscribe.list_tv
* Add MailChimpSubscribe to your FormIt hooks
* Add in your chunk the placeholder fi.error.mailchimp, which holds all MailChimp error messages.
* Add a field called newsgroup, if the value of this field is set to yes, the user will be subscribed to the mailchimp list.
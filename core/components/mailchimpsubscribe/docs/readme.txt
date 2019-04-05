---------------------------------------
MailChimpSubscribe
---------------------------------------
Version: 1.2.1-pl
Author: Sterc <modx@sterc.nl>
---------------------------------------

MailChimpSubscribe

Package for subscribing users in Mailchimp lists using FormIt.
Adds a snippet for retrieving Mailchimp lists in a single select TV and a
FormIt hook for subscribing Mailchimp users to the list provided in the pages Template Variable.

Functionalities

* FormIt hook for subscribing users to Mailchimp lists based on TV value
* MailChimpGetLists: Snippet for creating a select list of Mailchimp lists.

How to get set up

* Add Mailchimp API Key in systemsettings: mailchimpsubscribe.mailchimp_api_key
* Create a new single select TV variable and set the input option values to:
    @EVAL return $modx->runSnippet('MailChimpGetLists');
* Add Mailchimp List ID TV in systemsettings: mailchimpsubscribe.list_tv
* Add MailChimpSubscribe to your FormIt hooks
* Add in your chunk the placeholder fi.error.mailchimp, which holds all Mailchimp error messages.
* Add a field called newsgroup, if the value of this field is set to yes, the user will be subscribed to the mailchimp list.

Properties

* mailchimpListId: Mailchimp List ID.
* mailchimpFields: Configuration containing the field mapping between FormIt form and Mailchimp merge tags.
* mailchimpSubscribeStatus: Able to set the subscription status (subscribed, unsubscribed, pending, cleaned), default is pending.
* mailchimpSubscribeField: Field name to use for subscribing users to mailchimp.
* mailchimpSubscribeFieldValue: Field value to use for subscribing users to mailchimp.
* mailchimpTags: Comma separated tags you want the added subscriber to have. (Required mailchimpSubscribeStatus to be subscribed)

Basic usage without tags
[[!FormIt?
    &hooks=`MailChimpSubscribe,redirect`
    &validate=`email:email:required,name:required`
    &redirectTo=`[[++page_newsletter_thanks]]`
    &validationErrorMessage=`true`
    &store=`1`
    &submitVar=`newsletter-submit`
    &mailchimpListId=`12345678abc`
    &mailchimpFields=`name=FNAME,email=EMAIL`
    &mailchimpSubscribeField=`newsletter`
    &mailchimpSubscribeFieldValue=`1`
]]

Implementation with tags.
[[!FormIt?
    &hooks=`MailChimpSubscribe,redirect`
    &validate=`email:email:required,name:required`
    &redirectTo=`[[++page_newsletter_thanks]]`
    &validationErrorMessage=`true`
    &store=`1`
    &submitVar=`newsletter-submit`
    &mailchimpListId=`12345678abc`
    &mailchimpSubscribeStatus=`subscribed` <-- Important, if the status is not subscribed then the tags won't be added.
    &mailchimpFields=`name=FNAME,email=EMAIL`
    &mailchimpTags=`contactform,new-lead`
    &mailchimpSubscribeField=`newsletter`
    &mailchimpSubscribeFieldValue=`1`
]]
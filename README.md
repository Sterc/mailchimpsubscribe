# MailChimpSubscribe #

Package for subscribing users in Mailchimp lists using FormIt. Adds a snippet for retrieving Mailchimp lists in a single select TV and a FormIt hook for subscribing Mailchimp users to the list provided in the pages Template Variable.

## Functionalities ###

* FormIt hook for subscribing users to Mailchimp lists based on TV value
* MailChimpGetLists: Snippet for creating a select list of MailChimp lists.

## How do I get set up? ###

* Add MailChimp API Key in systemsettings: mailchimpsubscribe.mailchimp_api_key
* **Option 1:** Create a new single select TV variable and set the input option values to:    
    
```
#!php

@EVAL return $modx->runSnippet('MailChimpGetLists');
```
* Add Mailchimp List ID TV in systemsettings: mailchimpsubscribe.list_tv
* **Option 2:** Set the FormIt scriptProperty &mailchimpListId to the correct Mailchimp List ID
* Add MailChimpSubscribe to your FormIt hooks
* Add in your chunk the placeholder fi.error.mailchimp, which holds all Mailchimp error messages.
* Add a field called newsgroup, if the value of this field is set to yes, the user will be subscribed to the mailchimp list.

## Properties
| Property                     | Description                                                                              | Default value |
|------------------------------|------------------------------------------------------------------------------------------|---------------|
| mailchimpListId              | Mailchimp List ID.                                                                       |               |
| mailchimpFields              | Configuration containing the field mapping between FormIt form and Mailchimp merge tags. |               |
| mailchimpSubscribeStatus     | Able to set the subscription status (subscribed, unsubscribed, pending, cleaned), default is pending. |               |
| mailchimpSubscribeField      | Field name to use for subscribing users to Mailchimp.                                    | newsgroup     |
| mailchimpSubscribeFieldValue | Field value to use for subscribing users to Mailchimp.                                   | yes           |
| mailchimpTags                | Comma separated tags you want the added subscriber to have. (Required mailchimpSubscribeStatus to be subscribed) |               |

**Note**
The property `mailchimpFields` always requires a field to be set for the merge tag `EMAIL`, for example: email=EMAIL.

### Dependencies ###

* FormIt


### Example usage ###
The example below uses the provided Mailchimp list ID from the current resources TV where the Mailchimp List ID is set. It is also possible to use a scriptProperty to set the MailChimp list ID. Therefore add the following to your FormIt hook:
```
&mailchimpListId
```

####Basic usage without tags####
```
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

####Implementation with tags####
```
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
```

```
#!html
<form action="[[~[[*id]]]]" role="form" method="post" novalidate>
    <input type="hidden" name="nospam" value="" />
    <input type="hidden" name="newsletter" value="1"/>
    
    [[!+fi.error.mailchimp:notempty=`
        <p class="error">[[!+fi.error.mailchimp]]</p>
    `]]
           
    <div class="form-group [[!+fi.error.name:notempty=`has-error`]]">
        <input type="text" name="name" placeholder="Name" value="[[!+fi.name]]">
    </div>
        
    <div class="form-group [[!+fi.error.email:notempty=`has-error`]]">
        <input type="text" name="email" placeholder="Email" value="[[!+fi.email]]">
    </div>
    
    <div class="form-group [[!+fi.error.company_name:notempty=`has-error`]]">
        <input type="text" name="company_name" placeholder="Company" value="[[!+fi.company_name]]">
    </div>
    
    <div class="form-group">
        <input type="submit" name="newsletter-submit" value="Submit">
    </div>
        
</form>
```
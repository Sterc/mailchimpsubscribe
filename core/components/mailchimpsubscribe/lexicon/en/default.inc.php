<?php
/**
 * Default English Lexicon Entries for MailChimpSubscribe
 *
 * @package mailchimpsubscribe
 * @subpackage lexicon
 */

$_lang['mailchimpsubscribe'] = 'MailChimpSubscribe';

$_lang['setting_mailchimpsubscribe.mailchimp_api_key']      = 'MailChimp API key';
$_lang['setting_mailchimpsubscribe.mailchimp_api_key_desc'] = 'API key can be found in MailChimp under your Profile --> Extras --> API keys';
$_lang['setting_mailchimpsubscribe.list_tv']                = 'TV containing Mailchimp lists';
$_lang['setting_mailchimpsubscribe.list_tv_desc']           = 'Enter either the ID or the name of the TV';

$_lang['mailchimpsubscribe.error.no_list_found'] = 'Subscription failed: No list was provided to subscribe to.';
$_lang['mailchimpsubscribe.error.subscribed']    = 'You\'ve already been subscribed to this mailing list.';
$_lang['mailchimpsubscribe.error.pending']       = 'A subscription to this mailing list is already pending.
Please confirm your subscription with the email you\'ve received in your mail inbox';
$_lang['mailchimpsubscribe.error.cleaned']       = 'The provided E-mailaddress has been removed
from the mailing list due to failing to deliver emails to this E-mailaddress.';
$_lang['mailchimpsubscribe.error.missing_field_config_scriptproperty'] = 'The scriptproperty mailchimpFields was not found, please configure the merge tags using this scriptproperty.';
$_lang['mailchimpsubscribe.error.missing_required_config_field'] = 'Missing required config field for merge tag: [[+tag]]. Please add this field into the mailchimpFields scriptproperty.';
$_lang['mailchimpsubscribe.error.incorrect_status'] = 'Your status in incorrect for processing tags, make sure it\'s set to subscribed';
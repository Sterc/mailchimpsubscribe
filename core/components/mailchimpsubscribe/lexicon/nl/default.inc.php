<?php
/**
 * Default Dutch Lexicon Entries for MailChimpSubscribe
 *
 * @package mailchimpsubscribe
 * @subpackage lexicon
 */

$_lang['mailchimpsubscribe'] = 'MailChimpSubscribe';

$_lang['setting_mailchimpsubscribe.mailchimp_api_key']      = 'MailChimp API key';
$_lang['setting_mailchimpsubscribe.mailchimp_api_key_desc'] = 'API key can be found in MailChimp under your Profile --> Extras --> API keys';
$_lang['setting_mailchimpsubscribe.list_tv']                = 'TV containing Mailchimp lists';
$_lang['setting_mailchimpsubscribe.list_tv_desc']           = 'Enter either the ID or the name of the TV';

$_lang['mailchimpsubscribe.error.no_list_found'] = 'Inschrijving mislukt: Er kon geen lijst worden gevonden.';
$_lang['mailchimpsubscribe.error.subscribed']    = 'U bent al ingeschreven op de nieuwsbrief.';
$_lang['mailchimpsubscribe.error.pending']       = 'Uw inschrijving voor de nieuwsbrief dient nog te worden bevestigd.
U kunt deze bevestigen middels de email die u ontvangen heeft in uw inbox.';
$_lang['mailchimpsubscribe.error.cleaned']       = 'Het door u opgegeven e-mailadres is verwijderd van de nieuwsbrief lijst aangezien emails niet konden
worden afgeleverd op dit e-mailadres.';
$_lang['mailchimpsubscribe.error.missing_field_config_scriptproperty'] = 'De scriptproperty mailchimpFields ontbreekt. Koppel de velden aan de MailChimp merge tags door deze scriptproperty toe te voegen.';
$_lang['mailchimpsubscribe.error.missing_required_config_field'] = 'Het verplichte merge tag veld [[+tag]] kon niet worden gevonden in de mailchimpFields scriptproperty.';
$_lang['mailchimpsubscribe.error.incorrect_status'] = 'Incorrecte status voor verwerking van Tags, zet de status naar subscribed';
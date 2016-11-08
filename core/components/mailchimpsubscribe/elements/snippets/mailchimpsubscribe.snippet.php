<?php
$mailChimpSubscribe = $modx->getService(
    'mailchimpsubscribe',
    'MailChimpSubscribe',
    $modx->getOption(
        'mailchimpsubscribe.core_path',
        null,
        $modx->getOption('core_path') . 'components/mailchimpsubscribe/'
    ) . 'model/mailchimpsubscribe/'
);

if (!($mailChimpSubscribe instanceof MailChimpSubscribe)) {
    return;
}

return $mailChimpSubscribe->subscribeMailChimp($hook);
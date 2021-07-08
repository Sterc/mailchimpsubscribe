<?php
require_once dirname(dirname(dirname(__FILE__))) . '/libs/mailchimp-rest-api/src/VPS/MailChimp.php';

class MailChimpSubscribe
{
    const MC_ERROR_PH = 'mailchimp';

    /**
     * The modX object.
     *
     * @since    1.0.0
     * @access   public
     * @var      modX      The modX object.
     */
    public $modx;

    /**
     * @var \VPS\MailChimp
     */
    public $mailchimp;

    /**
     * The namespace for this package.
     *
     * @since    1.0.0
     * @access   public
     * @var      string         The package namespace.
     */
    public $namespace = 'mailchimpsubscribe';

    /**
     * Holds all configs values.
     *
     * @since    1.0.0
     * @access   public
     * @var      array          Config value holder.
     */
    public $config = [];

    /**
     * @var Sterc\FormIt\Hook
     */
    public $hook;

    /**
     * Holds mailchimp message for subscribing a user.
     *
     * @since    1.0.0
     * @access   public
     * @var      string          Mailchimp result message.
     */
    public $mcSubscribeMessage = '';

    /**
     * Holds the name or id of the MailChimp list TV.
     *
     * @var string
     */
    public $mcListTV = '';

    /**
     * Should a user be created or should an existing user be updated.
     *
     * @since   1.0.0
     * @access  private
     * @var     string          Mailchimp subscribe mode.
     */
    private $mcSubscribeMode = 'create';

    /**
     * Array containing mergefields and form fields mapping.
     * @var array
     */
    private $mapping = [];

    /**
     * Array containing all mergefields and form values.
     *
     * @var array
     */
    private $values = [];

    /**
     * Name of field used for subscribing to mailchimp newsletter.
     * @var string
     */
    private $subscribeField = 'newsgroup';

    /**
     * Value that the subscribe field should have for subscribing a user to mailchimp.
     * @var string
     */
    private $subscribeFieldValue = 'yes';

    /**
     * Prevent mailchimpSubscribe from stopping formit proccess
     * @var bool
     */
    private $valideSubscription = false;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    modX    $modx    The modX object.
     * @param    array   $config  Array with config values.
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx      =& $modx;
        $this->namespace = $this->modx->getOption('namespace', $config, 'mailchimpsubscribe');

        $corePath = $this->modx->getOption(
            'mailchimpsubscribe.core_path',
            $config,
            $this->modx->getOption('core_path')   . 'components/mailchimpsubscribe/'
        );

        $assetsUrl = $this->modx->getOption(
            'mailchimpsubscribe.assets_url',
            $config,
            $this->modx->getOption('assets_url')  . 'components/mailchimpsubscribe/'
        );

        $assetsPath = $this->modx->getOption(
            'mailchimpsubscribe.assets_path',
            $config,
            $this->modx->getOption('assets_path') . 'components/mailchimpsubscribe/'
        );

        $this->config = array_merge([
             'namespace'     => $this->namespace,
             'corePath'      => $corePath,
             'modelPath'     => $corePath . 'model/',
             'chunksPath'    => $corePath . 'elements/chunks/',
             'snippetsPath'  => $corePath . 'elements/snippets/',
             'templatesPath' => $corePath . 'templates/',
             'assetsPath'    => $assetsPath,
             'assetsUrl'     => $assetsUrl,
             'jsUrl'         => $assetsUrl . 'js/',
             'cssUrl'        => $assetsUrl . 'css/',
             'connectorUrl'  => $assetsUrl . 'connector.php'
         ], $config);

        $this->mcListTV = $this->modx->getOption('mailchimpsubscribe.list_tv');
        $this->mcListTV = is_numeric($this->mcListTV) ? (int) $this->mcListTV : (string) $this->mcListTV;

        $this->modx->addPackage('mailchimpsubscribe', $this->config['modelPath']);
        $this->modx->lexicon->load('mailchimpsubscribe:default');
    }

    /**
     * @param $hook
     */
    private function setHook($hook)
    {
        $this->hook = $hook;
    }

    private function setSubscribeFields()
    {
        if ($this->hook->config['mailchimpSubscribeField']) {
            $this->subscribeField = $this->hook->config['mailchimpSubscribeField'];
        }

        if ($this->hook->config['mailchimpSubscribeFieldValue']) {
            $this->subscribeFieldValue = $this->hook->config['mailchimpSubscribeFieldValue'];
        }
    }

    /**
     * Set the mailchimp merge tags configuration or add an error.
     *
     * @return bool
     */
    private function setMapping()
    {
        $config = $this->hook->config['mailchimpFields'];
        $fields = array_filter(explode(',', $config));

        if (!$fields) {
            $this->hook->addError(self::MC_ERROR_PH, $this->modx->lexicon('mailchimpsubscribe.error.missing_field_config_scriptproperty'));
            return false;
        }

        foreach ($fields as $field) {
            list($fieldName, $mergeTag) = explode('=', $field);

            $this->mapping[$mergeTag] = $fieldName;
        }

        if (!array_key_exists('EMAIL', $this->mapping)) {
            $this->hook->addError(self::MC_ERROR_PH, $this->modx->lexicon('mailchimpsubscribe.error.missing_required_config_field', ['tag' => 'EMAIL']));
            return false;
        }

        return true;
    }

    /**
     * Set the values array which contains all merge tags and form values which will be pushed to mailchimp.
     */
    private function setValues()
    {
        foreach ($this->mapping as $mergeTag => $fieldname) {
            if ($mergeTag !== 'EMAIL') {
                $this->values[$mergeTag] = $this->formatValue($this->hook->getValue($fieldname));
            }
        }
    }

    /**
     * Init MailChimp REST Enabled API 3.0 Wrapper Class.
     *
     * https://github.com/vatps/mailchimp-rest-api
     */
    private function initMailChimpApi()
    {
        $this->mailchimp = new \VPS\MailChimp($this->modx->getOption('mailchimpsubscribe.mailchimp_api_key'));
    }

    /**
     * Set mailchimp list id based on scriptproperty if it is set, else use tv value.
     *
     * @param $scriptProperties
     *
     * @return null
     */
    private function setListId($scriptProperties)
    {
        if (isset($scriptProperties['mailchimpListId']) && !empty($scriptProperties['mailchimpListId'])) {
            $listId = $scriptProperties['mailchimpListId'];
        } else {
            $listId = $this->modx->resource->getTVValue($this->mcListTV);
        }

        return $listId;
    }

    /**
     * Check if subscription needs to be validated
     *
     * @param $scriptProperties
     *
     * @return null
     */
    private function setValidateSubscription($scriptProperties)
    {
        if (isset($scriptProperties['mailchimpValidate']) &&
            !empty($scriptProperties['mailchimpValidate'])) {
            $this->valideSubscription = $scriptProperties['mailchimpValidate'];
        }

        return true;
    }

    /**
     * Set mailchimp subscriber status on subscription.
     *
     * @param $scriptProperties
     *
     * @return null
     */
    private function setSubscriberStatus($scriptProperties)
    {
        if (isset($scriptProperties['mailchimpSubscribeStatus']) &&
            !empty($scriptProperties['mailchimpSubscribeStatus'])) {
            $status = $scriptProperties['mailchimpSubscribeStatus'];
        } else {
            $status = 'pending';
        }

        return $status;
    }

    /**
     * Retrieve all MailChimp lists as TV select options.
     *
     * @return string
     */
    public function getMailChimpLists()
    {
        $this->initMailChimpApi();

        $params = array('count' => 100);
        $result = $this->mailchimp->get('/lists/?' . http_build_query($params));

        $options = [];
        if (isset($result['lists']) && !empty($result['lists'])) {
            foreach ($result['lists'] as $list) {
                $options[$list['name']] = $list['name'] . '==' . $list['id'];
            }
        }

        asort($options, SORT_NATURAL);
        array_unshift($options, '- Select a mailchimp list - ==0');

        return implode('||', $options);
    }

    /**
     *
     * @param $hook
     * @param $scriptProperties
     * @return string
     */
    public function subscribeMailChimp($hook, $scriptProperties)
    {
        $this->modx->lexicon->load('mailchimpsubscribe:default');

        /* Set hook. */
        $this->setHook($hook);

        /* Initialize mailchimp api. */
        $this->initMailChimpApi();

        /* Set subscribe fields based on scriptproperties. */
        $this->setSubscribeFields();
        $this->setValidateSubscription($scriptProperties);

        /* Fetch data from hook */
        $values = $this->hook->getValues();
        if ($values[$this->subscribeField] !== $this->subscribeFieldValue) {
            return true;
        }

        /* Validate the properties */
        $valid = $this->setMapping();
        if (!$valid) {
            return false;
        }

        $listId = $this->setListId($scriptProperties);
        $email  = $this->formatValue($this->hook->getValue($this->mapping['EMAIL']));
        $this->setValues();

        /* No list id found. */
        if (empty($listId)) {
            $this->hook->addError(
                self::MC_ERROR_PH,
                $this->modx->lexicon('mailchimpsubscribe.error.no_list_found', [], $this->modx->cultureKey)
            );

            return false;
        }

        /* Check if user is allowed to be processed */
        $subscribeUser = $this->checkMCSubscriberStatus($listId, $email);
        if ($subscribeUser === false && $this->valideSubscription) {
            $this->hook->addError(self::MC_ERROR_PH, $this->mcSubscribeMessage);

            return false;
        }

        /**
         * If an existing user is not subscribed, update existing user and set status to pending.
         * Which will send the user an emailconfirmation for the subscribtion.
         *
         * Subscribe statusses:
         * - subscribed: To add an address right away.
         * - pending: To send a confirmation email.
         * - unsubscribed/cleaned to archive unused addresses.
         *
         * http://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
         */
        $subscribeStatus = $this->setSubscriberStatus($scriptProperties);

        /* Check if tags are set and status is not subscribed */
        if ($subscribeStatus !== 'subscribed' &&
            !empty($scriptProperties['mailchimpTags'])  &&
            isset($scriptProperties['mailchimpTags'])
        ) {
            $this->hook->addError(
                self::MC_ERROR_PH,
                $this->modx->lexicon(
                    'mailchimpsubscribe.error.incorrect_status',
                    [],
                    $this->modx->cultureKey
                )
            );

            return false;
        }

          $params = [
            'email_address' => $email,
            'status'        => $subscribeStatus,
            'merge_fields'  => (object) $this->values
        ];

        if ($this->mcSubscribeMode === 'update') {
            $result = $this->mailchimp->PATCH('/lists/' . $listId . '/members/' . md5($email), $params);
        } else {
            $result = $this->mailchimp->post('/lists/' . $listId . '/members', $params);
        }
        
        if ($result['status'] !== $subscribeStatus && $this->valideSubscription) {
            // Log the detailed error so it's easier to debug problems
            $this->modx->log(modX::LOG_LEVEL_ERROR, print_r($result, true));
            
            $response = $result['title'] . ': '  . $result['detail'];

            $this->hook->addError(self::MC_ERROR_PH, $response);

            return false;
        }

        /* Add tags to subscription (Has no error handeling to prevent the process being killed) */
        if ($subscribeStatus === 'subscribed' &&
            !empty($scriptProperties['mailchimpTags'])  &&
            isset($scriptProperties['mailchimpTags'])
        ) {
            $this->addTags($listId, $email, $scriptProperties['mailchimpTags']);
        }

        return true;
    }

    /**
     * Check mailchimp subscriber status by emailaddress.
     *
     * @param string            $listId     List id as specified in resource TV.
     * @param string            $email      Form value emailaddress.
     *
     * @return  bool
     */
    public function checkMCSubscriberStatus($listId, $email)
    {
        $this->modx->lexicon->load('mailchimpsubscribe:default');

        $result = $this->mailchimp->get('/lists/' . $listId . '/members/' . md5($email));

        /* If status 404, the e-mailaddress is unknown and e-mailaddress can be subscribed. */
        if ($result['status'] === 404) {
            return true;
        }

        switch ($result['status']) {
            case 'subscribed':
                $this->mcSubscribeMessage = $this->modx->lexicon(
                    'mailchimpsubscribe.error.subscribed',
                    [],
                    $this->modx->cultureKey
                );
                break;
            case 'unsubscribed':
                /**
                 * Update user and set status to pending.
                 */
                $this->mcSubscribeMode = 'update';

                return true;
                break;
            case 'pending':
                $this->mcSubscribeMessage = $this->modx->lexicon(
                    'mailchimpsubscribe.error.pending',
                    [],
                    $this->modx->cultureKey
                );
                break;
            case 'cleaned':
                $this->mcSubscribeMessage = $this->modx->lexicon(
                    'mailchimpsubscribe.error.cleaned',
                    [],
                    $this->modx->cultureKey
                );
                break;
        }

        return false;
    }

    /**
     * Processes comma separated value to tags for subscribes
     *
     * @param $listId
     * @param $email
     * @param $tags
     * @return string
     */
    public function addTags($listId, $email, $tags)
    {
        $hashed = md5($email);
        $data = [];

        $tags = explode(',', $tags);

        /* Needs array and status active to create tag if not existing */
        foreach ($tags as $value) {
            $data[] = [
                'name'   => $value,
                'status' => 'active'
            ];
        }

        /* Post data to mailchimp */
        $result = $this->mailchimp->post(
            '/lists/' . $listId . '/members/' . $hashed . '/tags',
            [
                'tags'  => $data
            ]
        );

        return $result;
    }

    /**
     * Format form values.
     *
     * @param $value
     * @return string
     */
    private function formatValue($value) {
        return trim($value);
    }
}

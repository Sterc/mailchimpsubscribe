<?php
require_once dirname(dirname(dirname(__FILE__))) . '/libs/mailchimp-rest-api/src/VPS/MailChimp.php';

class MailChimpSubscribe
{

    /**
     * The modX object.
     *
     * @since    1.0.0
     * @access   public
     * @var      null|modX      The modX object.
     */
    public $modx = null;

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
     * Holds the List field used for name.
     *
     * @since   1.0.0
     * @access  private
     * @var     string              Mailchimp list field for name.
     */
    private $mcFieldName = 'FNAME';

    /**
     * Holds the List field used for company.
     *
     * @since   1.0.0
     * @access  private
     * @var     string              Mailchimp list field for company.
     */
    private $mcFieldCompany = 'FCOMPANY';

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     * @param    modX    $modx    The modX object.
     * @param    array   $config  Array with config values.
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx =& $modx;
        $this->namespace = $this->modx->getOption('namespace', $config, 'mailchimpsubscribe');

        $basePath = $this->modx->getOption(
            'site.core_path',
            $config,
            $this->modx->getOption('core_path')   . 'components/site/'
        );

        $assetsUrl = $this->modx->getOption(
            'site.assets_url',
            $config,
            $this->modx->getOption('assets_url')  . 'components/site/'
        );

        $assetsPath = $this->modx->getOption(
            'site.assets_path',
            $config,
            $this->modx->getOption('assets_path') . 'components/site/'
        );

        $this->config = array_merge([
            'base_path'       => $basePath,
            'core_path'       => $basePath,
            'model_path'      => $basePath . 'model/',
            'processors_path' => $basePath . 'processors/',
            'elements_path'   => $basePath . 'elements/',
            'templates_path'  => $basePath . 'templates/',
            'assets_path'     => $assetsPath,
            'js_url'          => $assetsUrl . 'js/',
            'css_url'         => $assetsUrl . 'css/',
            'assets_url'      => $assetsUrl,
            'connector_url'   => $assetsUrl . 'connector.php',
        ], $config);

        $this->mcListTV = $this->modx->getOption('mailchimpsubscribe.list_tv');
        $this->mcListTV = (is_numeric($this->mcListTV)) ? (int) $this->mcListTV : (string) $this->mcListTV;

        $this->modx->addPackage('mailchimpsubscribe', $this->config['model_path']);
        $this->modx->lexicon->load('mailchimpsubscribe:default');
    }

    /**
     * Init MailChimp REST Enabled API 3.0 Wrapper Class.
     *
     * https://github.com/vatps/mailchimp-rest-api
     *
     * @return \VPS\MailChimp
     */
    private function initMailChimpApi()
    {
        $mcApiKey = $this->modx->getOption('mailchimpsubscribe.mailchimp_api_key');
        $mc       = new \VPS\MailChimp($mcApiKey);

        return $mc;
    }

    /**
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

        var_dump($listId);exit;

        return $listId;
    }

    /**
     * Retrieve all MailChimp lists as TV select options.
     *
     * @return string
     */
    public function getMailChimpLists()
    {
        $mc     = $this->initMailChimpApi();
        $result = $mc->get('/lists/');

        $tvOutput = '- Select a mailchimp list - ==0';
        if (isset($result['lists']) && !empty($result['lists'])) {
            foreach ($result['lists'] as $list) {
                $tvOutput .= '||' . $list['name'] . '==' . $list['id'];
            }
        }

        return $tvOutput;
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

        $mc     = $this->initMailChimpApi();
        $values = $hook->getValues();
        if ($values['newsgroup'] != 'yes') {
            return true;
        }

        $listId    = $this->setListId($scriptProperties);
        $userEmail = trim(strtolower($values['email']));
        $userName  = trim($values['name']);
        $company   = trim($values['company_name']);

        /**
         * No list id found.
         */
        if (!isset($listId) || $listId === 0 || empty($listId)) {
            $hook->addError(
                'mailchimp',
                $this->modx->lexicon('mailchimpsubscribe.error.no_list_found', [], $this->modx->context->key)
            );
            $hook->hasErrors();
            $this->modx->setPlaceholder('fi.validation_error', true);

            return false;
        }

        $subscribeUser = $this->checkMCSubscriberStatus($mc, $listId, $userEmail);
        if ($subscribeUser === false) {
            $hook->addError('mailchimp', $this->mcSubscribeMessage);
            $hook->hasErrors();
            $this->modx->setPlaceholder('fi.validation_error', true);

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
        $subscribeStatus = 'pending';
        if ($this->mcSubscribeMode === 'update') {
            $result = $mc->PATCH(
                '/lists/'. $listId . '/members/' . md5($userEmail),
                [
                     'email_address' => $userEmail,
                     'merge_fields'  => [
                         $this->mcFieldName    => $userName,
                         $this->mcFieldCompany => $company
                     ],
                     'status' => $subscribeStatus
                ]
            );
        } else {
            $result = $mc->post(
                '/lists/'. $listId . '/members',
                [
                    'email_address' => $userEmail,
                    'merge_fields'  => [
                        $this->mcFieldName    => $userName,
                        $this->mcFieldCompany => $company
                    ],
                    'status' => $subscribeStatus
                ]
            );
        }

        if ($result['status'] != $subscribeStatus) {
            $response = $result['title'] . ': '  . $result['detail'];

            $hook->addError('mailchimp', $response);
            $hook->hasErrors();
            $this->modx->setPlaceholder('fi.validation_error', true);

            return false;
        }

        return true;
    }

    /**
     * Check mailchimp subscriber status by emailaddress.
     *
     * @param \VPS\MailChimp    $mc         MailChimp REST API Object.
     * @param string            $listId     List id as specified in resource TV.
     * @param string            $email      Form value emailaddress.
     *
     * @return  bool
     */
    public function checkMCSubscriberStatus($mc, $listId, $email)
    {
        $this->modx->lexicon->load('mailchimpsubscribe:default');
        $result = $mc->get('/lists/' . $listId . '/members/' . md5($email));

        /**
         * If status 404, the emailaddress is unknown and emailaddress can be subscribed.
         */
        if ($result['status'] == '404') {
            return true;
        }

        switch ($result['status']) {
            case 'subscribed':
                $this->mcSubscribeMessage = $this->modx->lexicon(
                    'mailchimpsubscribe.error.subscribed',
                    [],
                    $this->modx->context->key
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
                    $this->modx->context->key
                );
                break;
            case 'cleaned':
                $this->mcSubscribeMessage = $this->modx->lexicon(
                    'mailchimpsubscribe.error.cleaned',
                    [],
                    $this->modx->context->key
                );
                break;
        }

        return false;
    }
}
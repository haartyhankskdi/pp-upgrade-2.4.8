<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Helper;

use Magento\Framework\Exception\LocalizedException;
use Mirasvit\Helpdesk\Model\Config as Config;
use Mirasvit\Helpdesk\Model\ResourceModel\Priority\CollectionFactory as PriorityCollectionFactory;
use Mirasvit\Helpdesk\Model\TicketFactory;
use Magento\Sales\Model\Order\AddressFactory;
use Mirasvit\Helpdesk\Model\GatewayFactory;
use Magento\Sales\Model\OrderFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory as TicketCollectionFactory;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory as DepartmentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory as QuoteAddressCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Pattern\CollectionFactory as PatternCollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Escaper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObjectFactory;
/**
 * Class Process.
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Process
{
    /**
     * @var int|null
     */
    private $orderLength = null;

    private const PATTERN_NAME = '/(?:[\p{L}\p{M}\,\-\_\.\'’`\s\d]){1,255}+/u';

    private $ticketFactory;

    private $orderAddressFactory;

    private $gatewayFactory;

    private $orderFactory;

    private $ticketCollectionFactory;

    private $userCollectionFactory;

    private $departmentCollectionFactory;

    private $orderCollectionFactory;

    private $quoteAddressCollectionFactory;

    private $patternCollectionFactory;

    private $messageCollectionFactory;

    private $customerCollectionFactory;

    private $config;

    private $helpdeskCustomer;

    private $helpdeskString;

    private $helpdeskField;

    private $helpdeskTag;

    private $helpdeskDraft;

    private $helpdeskEncoding;

    private $storeManager;

    private $localeDate;

    private $customerSession;

    private $auth;

    private $context;

    private $escaper;

    private $priorityCollectionFactory;

    private $dataObjectFactory;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PriorityCollectionFactory $priorityCollectionFactory,
        TicketFactory $ticketFactory,
        AddressFactory $orderAddressFactory,
        GatewayFactory $gatewayFactory,
        OrderFactory $orderFactory,
        TicketCollectionFactory $ticketCollectionFactory,
        UserCollectionFactory $userCollectionFactory,
        DepartmentCollectionFactory $departmentCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        QuoteAddressCollectionFactory $quoteAddressCollectionFactory,
        PatternCollectionFactory $patternCollectionFactory,
        MessageCollectionFactory $messageCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        Config $config,
        Customer $helpdeskCustomer,
        StringUtil $helpdeskString,
        Field $helpdeskField,
        Tag $helpdeskTag,
        Draft $helpdeskDraft,
        Encoding $helpdeskEncoding,
        Escaper $escaper,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerSession $customerSession,
        Auth $auth,
        Context $context,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->priorityCollectionFactory     = $priorityCollectionFactory;
        $this->ticketFactory                 = $ticketFactory;
        $this->orderAddressFactory           = $orderAddressFactory;
        $this->gatewayFactory                = $gatewayFactory;
        $this->orderFactory                  = $orderFactory;
        $this->ticketCollectionFactory       = $ticketCollectionFactory;
        $this->userCollectionFactory         = $userCollectionFactory;
        $this->departmentCollectionFactory   = $departmentCollectionFactory;
        $this->orderCollectionFactory        = $orderCollectionFactory;
        $this->quoteAddressCollectionFactory = $quoteAddressCollectionFactory;
        $this->patternCollectionFactory      = $patternCollectionFactory;
        $this->messageCollectionFactory      = $messageCollectionFactory;
        $this->customerCollectionFactory     = $customerCollectionFactory;
        $this->config                        = $config;
        $this->helpdeskCustomer              = $helpdeskCustomer;
        $this->helpdeskString                = $helpdeskString;
        $this->helpdeskField                 = $helpdeskField;
        $this->helpdeskTag                   = $helpdeskTag;
        $this->helpdeskDraft                 = $helpdeskDraft;
        $this->helpdeskEncoding              = $helpdeskEncoding;
        $this->escaper                       = $escaper;
        $this->storeManager                  = $storeManager;
        $this->localeDate                    = $localeDate;
        $this->customerSession               = $customerSession;
        $this->auth                          = $auth;
        $this->context                       = $context;
        $this->dataObjectFactory             = $dataObjectFactory;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    private function isValidName($nameValue): bool
    {
        if ($nameValue != null) {
            if (preg_match(self::PATTERN_NAME, $nameValue, $matches)) {
                return $matches[0] == $nameValue;
            }
        }

        return true;
    }

    /**
     * @param $post
     * @return void
     * @throws LocalizedException
     */
    protected function validateInput($post) {
        if (isset($post['customer_name']) && !($this->isValidName($post['customer_name']))) {
            throw new LocalizedException(__('Field "Name" is incorrect.'));
        }

        $emailValidator = new \Magento\Framework\Validator\EmailAddress();
        if (isset($post['customer_email']) && !$emailValidator->isValid(trim($post['customer_email']))) {
            throw new LocalizedException(__('Incorrect email format.'));
        }
    }

    /**
     * Creates ticket from frontend.
     *
     * @param array  $post
     * @param string $channel
     *
     * @return \Mirasvit\Helpdesk\Model\Ticket
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createFromPost($post, $channel)
    {
        $this->validateInput($post);

        $ticket = $this->ticketFactory->create();
        // find customer by email
        $customer = $this->helpdeskCustomer->getCustomerByPost($post);

        $ticket->setCustomerId($customer->getId())
            ->setCustomerEmail($customer->getEmail())
            ->setCustomerName($customer->getName())
            ->setQuoteAddressId($customer->getQuoteAddressId())
            ->setCode($this->helpdeskString->generateTicketCode())
            ->setSubject($post['subject']);
        //->setDescription($this->getEnviromentDescription());

        // to prevent exception "undefined index 'customer_email'"
        if ($customer && !isset($post['customer_email'])) {
            $post['customer_email'] = $customer->getEmail();
        }

        if (empty($post['customer_email'])) {
            throw new LocalizedException(
                __('Email field should not be empty')
            );
        }

        if (isset($post['priority_id'])) {
            $ticket->setPriorityId((int)$post['priority_id']);
        } else {
            $priorityIds = $this->priorityCollectionFactory->create()->getAllIds();
            if (in_array($this->config->getDefaultPriority(), $priorityIds)) {
                $ticket->setPriorityId($this->config->getDefaultPriority());
            } else {
                $ticket->setPriorityId(null);
            }
        }

        if (isset($post['department_id'])) {
            $ticket->setDepartmentId((int)$post['department_id']);
        } else {
            $ticket->setDepartmentId($this->getConfig()->getContactFormDefaultDepartment());
        }

        if (isset($post['order_id'])) {
            $ticket->setOrderId((int)$post['order_id']);
        }
        $ticket->setStoreId($this->storeManager->getStore()->getId());
        $ticket->setChannel($channel);

        if ($channel == Config::CHANNEL_FEEDBACK_TAB) {
            $url = $this->customerSession->getFeedbackUrl();
            if (!isset($post['channel_data'])) {
                $post['channel_data'] = [];
            }

            $post['channel_data']['url'] = $url;
        }

        if (isset($post['channel_data'])) {
            $ticket->setChannelData($post['channel_data']);
        }

        $this->helpdeskField->processPost($post, $ticket);
        $ticket->save();
        $pattern = $this->checkPostForSpamPattern($post);

        if ($pattern) {
            $ticket->markAsSpam();
        }

        if (empty($post['message'])) {
            return $ticket;
        }

        $body = $post['message'];

        if (!empty($post['current_url'])) {
            $body .= "\n\n" . __('Submitted from the page: ') . $this->escapeHtml($post['current_url']);
        }

        $ticket->addMessage(
            $body,
            $customer,
            false,
            Config::CUSTOMER,
            Config::MESSAGE_PUBLIC,
            false,
            Config::FORMAT_PLAIN
        );

        return $ticket;
    }

    /**
     * Escape HTML entities
     *
     * @param string|array $data
     * @param array|null   $allowedTags
     *
     * @return string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        //html can contain incorrect symbols which produce warrnings to log
        $internalErrors = libxml_use_internal_errors(true);
        $res            = $this->escaper->escapeHtml($data, $allowedTags);
        libxml_use_internal_errors($internalErrors);

        return $res;
    }

    /**
     * @return string
     */
    public function getEnviromentDescription()
    {
        return print_r($_SERVER, true);
    }

    /**
     * Create or update a ticket on behalf of an agent, or of an API client that has no agent identity.
     *
     * $user is null when the caller authenticated with a Magento Integration OAuth token. An integration
     * has no admin_user row and its id belongs to a different id space (integration.integration_id), so it
     * must never reach anything expecting an admin user id. With no agent identity the ticket is treated as
     * customer-originated: left unassigned unless the caller named an owner explicitly, and its first
     * message is recorded as the customer's.
     *
     * @param array                         $data
     * @param \Magento\User\Model\User|null $user Acting agent, or null for an integration (API) client
     *
     * @return \Mirasvit\Helpdesk\Model\Ticket
     * @throws \Magento\Framework\Exception\LocalizedException
     * @fixme
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function createOrUpdateFromBackendPost($data, $user = null)
    {
        $ticket = $this->ticketFactory->create();
        if (isset($data['ticket_id']) && (int)$data['ticket_id'] > 0) {
            $ticket->load((int)$data['ticket_id']);
        } else {
            unset($data['ticket_id']);
        }
        if (class_exists('\Magento\Framework\Validator\EmailAddress')) { // for m2.2.x
            $validator = new \Magento\Framework\Validator\EmailAddress();
        } else {
            $validator = new \Zend_Validate_EmailAddress();
        }
        if (!$validator->isValid($data['customer_email'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid Customer Email'));
        }

        if (!isset($data['customer_id']) || !$data['customer_id'] || $data['customer_id'] == 0) {

            $customers = $this->customerCollectionFactory->create();
            $customers
                ->addAttributeToSelect('*')
                ->addFieldToFilter('email', $data['customer_email']);

            if ($customers->count()) {
                $customer              = $customers->getFirstItem();
                $data['customer_id']   = $customer->getId();
                $data['customer_name'] = $customer->getEmail();
            }

            if (!$ticket->getCustomerName() && empty($customer)) {
                $data['customer_name'] = $data['customer_email'];
            }
        }

        if (isset($data['customer_id']) && strpos((string) $data['customer_id'], 'address_') !== false) {
            $data['quote_address_id'] = (int)str_replace('address_', '', $data['customer_id']);
            $data['customer_id']      = null;
            if ($data['quote_address_id']) {
                $quoteAddress = $this->orderAddressFactory->create();
                $quoteAddress->load($data['quote_address_id']);
                $data['customer_name'] = $quoteAddress->getName();
            }
        } else {
            $data['quote_address_id'] = null;
        }

        $ticket->addData($data);

        $this->helpdeskTag->setTags($ticket, $data['tags']);
        //set custom fields
        $this->helpdeskField->processPost($data, $ticket);
        //set ticket user and department
        if (isset($data['owner'])) {
            $data['owner'] = $this->modifyOwner(
                $data['owner'],
                $user ? $user->getId() : null,
                $ticket->getId()
            );
            $ticket->initOwner($data['owner']);
        }
        if (isset($data['fp_owner'])) {
            $ticket->initOwner($data['fp_owner'], 'fp');
        }
        if (isset($data['fp_period_unit']) && $data['fp_period_unit'] == 'custom') {
            $value = $ticket->getData('fp_execute_at');

            if (!empty($data['fp_execute_at'])) {
                $value = $this->localeDate->convertConfigTimeToUtc($value);
            }

            $ticket->setData('fp_execute_at', $value);
        } elseif (isset($data['fp_period_value']) && $data['fp_period_value']) {
            $ticket->setData('fp_execute_at', $this->createFpDate($data['fp_period_unit'], $data['fp_period_value']));
        }
        if (!$ticket->getId()) {
            $ticket->setChannel(Config::CHANNEL_BACKEND);
        }

        //We should add the empty reply to the locked tickets to re-save it if needed
        if (($ticket->getOrigData('status_id'))
            && (in_array($ticket->getOrigData('status_id'), $this->config->getGeneralLockedStatusList()))) {
            $data['reply'] = '';
        }

        $filesData = $this->context->getRequest()->getFiles();
        $hasReply  = trim((string)($data['reply'] ?? '')) || !empty($filesData['attachment'][0]['name']);
        if ($hasReply && $user) {
            $ticket->setMessageSender($user->getId());
        }
        $ticket->save();

        $bodyFormat = Config::FORMAT_PLAIN;
        if ($this->getConfig()->getGeneralIsWysiwyg()) {
            $bodyFormat = Config::FORMAT_HTML;
        }
        if ($hasReply) {
            if ($user) {
                $ticket->addMessage(
                    $data['reply'],
                    false,
                    $user,
                    Config::USER,
                    $data['reply_type'],
                    false,
                    $bodyFormat
                );
            } else {
                $ticket->addMessage(
                    $data['reply'],
                    $this->getCustomerMessageAuthor($ticket),
                    false,
                    Config::CUSTOMER,
                    $data['reply_type'],
                    false,
                    $bodyFormat
                );
            }
        }
        if ($ticket->getCustomerId() && isset($data['customer_note'])) {
            $ticket->addNote($data['customer_note']);
        }

        $this->helpdeskDraft->clearDraft($ticket);

        return $ticket;
    }

    /**
     * @param string   $dataOwner
     * @param int|null $userId Acting agent, or null for an integration (API) client
     * @param bool     $ticketId
     *
     * @return string
     */
    public function modifyOwner($dataOwner, $userId, $ticketId)
    {
        $owner = $dataOwner;
        $owner = explode('_', $owner);
        // Set owner for a new ticket. Without an acting agent there is no agent to fall back to,
        // so an unqualified owner stays unassigned rather than borrowing an unrelated agent's id.
        if (!$owner[1] && !$ticketId && $userId) {
            $departmentIds = $this->getUserDepartmentIds($userId);
            if (count($departmentIds) > 0) {
                if (!in_array($owner[0], $departmentIds)) {
                    $owner[0] = array_shift($departmentIds);
                }
                $owner[1] = $userId;
            }
        }
        $owner = $owner[0] . '_' . $owner[1];

        return $owner;
    }

    /**
     * Build the message author for a ticket that has no acting agent.
     *
     * Ticket::addMessage() needs a customer-shaped object (id/name/email) when the message is
     * attributed to the customer. The ticket already carries the resolved customer details, so
     * they are reused here instead of re-reading the session, which does not exist on an API request.
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return \Magento\Framework\DataObject
     */
    public function getCustomerMessageAuthor($ticket)
    {
        return $this->dataObjectFactory->create([
            'data' => [
                'id'    => $ticket->getCustomerId(),
                'name'  => $ticket->getCustomerName(),
                'email' => $ticket->getCustomerEmail(),
            ],
        ]);
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getUserDepartmentIds($userId)
    {
        $departments = $this->departmentCollectionFactory->create();
        $departments->addUserFilter($userId)->addFieldToFilter('is_active', true);

        return $departments->getAllIds();
    }

    /**
     * @param string $unit
     * @param int    $value
     *
     * @return string
     */
    public function createFpDate($unit, $value)
    {
        $timeshift = 0;
        switch ($unit) {
            case 'minutes':
                $timeshift = $value;
                break;
            case 'hours':
                $timeshift = $value * 60;
                break;
            case 'days':
                $timeshift = $value * 60 * 24;
                break;
            case 'weeks':
                $timeshift = $value * 60 * 24 * 7;
                break;
            case 'months':
                $timeshift = $value * 60 * 24 * 31;
                break;
        }
        $timeshift *= 60; //in seconds
        $time      = strtotime((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT))
            + $timeshift;
        $time      = date('Y-m-d H:i:s', $time);

        return $time;
    }

    /**
     * @return object
     */
    public function isDev()
    {
        return $this->config->getDeveloperIsActive();
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Email $email
     * @param string                         $code
     *
     * @return bool|\Mirasvit\Helpdesk\Model\Ticket
     * @throws \Exception
     * @fixme
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processEmail($email, $code)
    {
        $ticket      = false;
        $customer    = false;
        $user        = false;
        $triggeredBy = Config::CUSTOMER;
        $messageType = Config::MESSAGE_PUBLIC;

        if ($code) {
            //try to find customer for this email
            $tickets = $this->ticketCollectionFactory->create();
            $tickets->addFieldToFilter('code', $code)
                ->addFieldToFilter('customer_email', $email->getFromEmail());

            if ($tickets->count()) {
                $ticket = $tickets->getFirstItem();
            } else {
                //try to find staff user for this email
                $users = $this->userCollectionFactory->create()
                    ->addFieldToFilter('email', $email->getFromEmail());

                if ($users->count()) {
                    $user    = $users->getFirstItem();
                    $tickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('code', $code);
                    if ($tickets->count()) {
                        $ticket = $tickets->getFirstItem();
                        $ticket->setUserId($user->getId());
                        $ticket->save();
                        $triggeredBy = Config::USER;
                    } else {
                        $user = false; //@temp dva for testing
                    }
                } else { //third party
                    $tickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('code', $code);

                    if ($tickets->count()) {
                        $ticket      = $tickets->getFirstItem();
                        $triggeredBy = Config::THIRD;
                    }

                    if ($ticket && $ticket->isThirdPartyPublic($email->getFromEmail())) {
                        $messageType = Config::MESSAGE_PUBLIC_THIRD;
                    } else {
                        $messageType = Config::MESSAGE_INTERNAL_THIRD;
                    }
                }
            }
        }

        if (!$user) {
            $customer = $this->helpdeskCustomer->getCustomerByEmail($email);
        }

        // create a new ticket
        if (!$ticket) {
            // Auto-replies DO open tickets - that is what helpdesk/general/fetch_autoreplies asks
            // for, and a canned responder rarely quotes the [#code] needed to join the original
            // thread, so refusing to open one dropped them entirely. The loop is broken on the way
            // out instead, by Ticket::canNotify(): the ticket is created but nothing is mailed
            // back, so the responder is never prompted for another auto-reply.
            $ticket = $this->ticketFactory->create();

            if (!$code) {
                $ticket->setCode($this->helpdeskString->generateTicketCode());
            } else {
                $ticket->setCode($code);//temporary for testing to fix @dva
            }

            $gateway = $this->gatewayFactory->create()->load($email->getGatewayId());
            if ($gateway->getId()) {
                if ($gateway->getDepartmentId()) {
                    $ticket->setDepartmentId($gateway->getDepartmentId());
                } else { //if department was removed
                    $departments = $this->departmentCollectionFactory->create()
                        ->addFieldToFilter('is_active', true);

                    if ($departments->count()) {
                        $department = $departments->getFirstItem();
                        $ticket->setDepartmentId($department->getId());
                    } else {
                        $this->context->getLogger()->error(
                            'Helpdesk MX - Can\'t find any active department. Helpdesk can\'t fetch tickets correctly!'
                        );
                    }
                }

                $ticket->setStoreId($gateway->getStoreId());
            }

            $cc = '';

            if ($email->getCc() && ($this->config->getIsCcAdded() || $triggeredBy == Config::USER)) {
                $cc = $email->getCc();
            }

            $ticket
                ->setSubject($email->getSubject())
                ->setCustomerName($customer->getName())
                ->setCustomerId($customer->getId())
                ->setQuoteAddressId($customer->getQuoteAddressId())
                ->setCustomerEmail($email->getFromEmail())
                ->setChannel(Config::CHANNEL_EMAIL)
                ->setCc($cc)
            ;

            $ticket->setEmailId($email->getId());
            $ticket->save();
            $pattern = $this->checkForSpamPattern($email);
            if ($pattern) {
                $ticket->markAsSpam();
                if ($email) {
                    $email->setPatternId($pattern->getId())->save();
                }
            }
        }

        if ($customer && $this->getOrderLength()) {
            $orderLength = $this->getOrderLength();
            //parse order ID from email subject
            preg_match_all('[[0-9]{' . $orderLength . '}]', (string)$email->getSubject(), $numbers);

            foreach ($numbers[0] as $number) {
                $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('increment_id', $number)
                    ->addFieldToFilter('customer_id', $customer->getId());

                if (count($orders)) {
                    // Case 1: this is registered customer and has an order
                    $order = $this->orderFactory->create()->loadByAttribute('increment_id', $number);
                    $ticket->setCustomerId($order->getCustomerId());
                    $ticket->setOrderId($order->getId());
                    $ticket->save();
                    break;
                } else {
                    $order = $this->orderFactory->create()->loadByAttribute('increment_id', $number);
                    $ticket->setOrderId($order->getId());

                    // Case 2: this is known guest customer or known another email of registered customer
                    $prevTickets = $this->ticketCollectionFactory->create()
                        ->addFieldToFilter('customer_email', $email->getFromEmail())
                        ->addFieldToFilter('order_id', $order->getId());
                    if (count($prevTickets)) {
                        $ticket->setCustomerId($order->getCustomerId());
                        $ticket->save();
                        break;
                    }

                    // Case 3: this is generic guest customer with existing order
                    $quotes = $this->quoteAddressCollectionFactory->create();
                    $quotes
                        ->addFieldToFilter('email', $email->getFromEmail())
                        ->addFieldToFilter('customer_id', $customer->getId());
                    $quotes->getSelect()->group('email');

                    if ($quotes->count()) {
                        $ticket->setQuoteAddressId($quotes->getFirstItem()->getId());
                        $ticket->save();
                        break;
                    }
                }
            }
        }

        //add message to ticket
        $text           = $email->getBody();
        $encodingHelper = $this->helpdeskEncoding;
        $text           = $encodingHelper->toUTF8($text);

        //We should fetch a customer reply from the e-bay support html text separately
        if (preg_match('~@ebay\.com~', $email->getFromEmail(), $matches)
            || preg_match('~@members\.ebay~', $email->getFromEmail(), $matches)) {
            $text = $this->helpdeskString->getEbayCustomerMessageText($text);
        }

        $body = $this->helpdeskString->parseBody((string)$text, $email->getFormat());

        $ticket->addMessage($body, $customer, $user, $triggeredBy, $messageType, $email);

        $this->context->getEventManager()->dispatch(
            'helpdesk_process_email',
            [
                'body'        => $body,
                'customer'    => $customer,
                'user'        => $user,
                'ticket'      => $ticket,
                'triggeredBy' => $triggeredBy,
                'email'       => $email,
            ]
        );

        return $ticket;
    }

    /**
     * @return int
     */
    private function getOrderLength()
    {
        if ($this->orderLength === null) {
            $lastOrderCollection = $this->orderCollectionFactory->create();
            $lastOrderCollection->getSelect()
                ->limit(1)
                ->order('entity_id DESC');
            $this->orderLength = 0;
            if ($lastOrderCollection->count()) {
                /** @var \Magento\Sales\Model\Order $lastOrder */
                $lastOrder         = $lastOrderCollection->getLastItem();
                $this->orderLength = strlen((string)$lastOrder->getIncrementId());
            }
        }

        return $this->orderLength;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Email $email
     *
     * @return \Mirasvit\Helpdesk\Model\Pattern|false
     */
    public function checkForSpamPattern($email)
    {
        $patterns = $this->patternCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($patterns as $pattern) {
            if ($pattern->checkEmail($email)) {
                return $pattern;
            }
        }

        return false;
    }

    /**
     * @param array $post
     *
     * @return \Mirasvit\Helpdesk\Model\Pattern|false
     */
    public function checkPostForSpamPattern($post)
    {
        $patterns = $this->patternCollectionFactory->create()
            ->addFieldToFilter('is_active', true);
        foreach ($patterns as $pattern) {
            if ($pattern->checkPost($post)) {
                return $pattern;
            }
        }

        return false;
    }

    /**
     * Merge selected tickets.
     *
     * @param array $ids Array of ticket identifiers.
     *
     * @return void
     */
    public function mergeTickets($ids)
    {
        // Sort ids in ascending order
        sort($ids);

        $baseTicket = $this->ticketFactory->create()->load($ids[0]);

        // Get all messages, registered in selected tickets and merge it to oldest
        $mergeMessages = $this->messageCollectionFactory->create()
            ->addFieldToFilter('ticket_id', $ids);
        foreach ($mergeMessages as $msg) {
            $msg->setTicketId($baseTicket->getId());
            $msg->save();
        }

        // Add to merged tickets new message instead of moved ones
        $mergeMessage = __('Ticket was merged to %1', $baseTicket->getCode() . ' - ');
        /** @var \Magento\User\Model\User $user */
        $user       = $this->auth->getUser();
        $mergeCodes = [];
        foreach ($ids as $id) {
            if ($id == $baseTicket->getId()) {
                continue;
            }

            $ticket = $this->ticketFactory->create()->load($id);
            $ticket
                ->setMergedTicketId($baseTicket->getId())
                ->setFolder(Config::FOLDER_ARCHIVE)
                ->addMessage(
                    $mergeMessage . '[[ticket_url__' . $baseTicket->getId() . ']]',
                    null,
                    $user,
                    Config::USER,
                    Config::MESSAGE_INTERNAL
                )
                ->save();
            $ticket->close();
            $mergeCodes[] = $ticket->getCode();
        }
        $baseTicket->setMergedTicketCodes($mergeCodes)
            ->save();
    }
}

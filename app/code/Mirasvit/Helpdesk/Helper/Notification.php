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

use Mirasvit\Helpdesk\Model\Config as Config;
use Mirasvit\Helpdesk\Service\Email\VariableObjectFactory;

/**
 * Class Notification.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Notification extends \Magento\Framework\App\Helper\AbstractHelper
{
    const LIMIT_MAX_CC = 5;

    protected $transportBuilder;

    protected $ticketCollectionFactory;

    protected $departmentFactory;

    protected $inlineTranslation;

    protected $userFactory;

    protected $emailTemplateFactory;

    protected $config;

    protected $helpdeskEmail;

    protected $context;

    protected $productMetadata;

    protected $storeManager;

    protected $assetRepo;

    protected $filesystem;

    protected $design;

    protected $auth;

    protected $objectManager;

    private $fieldHelper;

    private $fieldFactory;

    private $translit;

    private $variableObjectFactory;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        VariableObjectFactory $variableObjectFactory,
        \Magento\Framework\Filter\Translit $translit,
        \Magento\User\Model\UserFactory $userFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Ticket\CollectionFactory $ticketCollectionFactory,
        \Mirasvit\Helpdesk\Model\DepartmentFactory $departmentFactory,
        \Mirasvit\Helpdesk\Model\FieldFactory $fieldFactory,
        \Mirasvit\Helpdesk\Model\Mail\Template\TransportBuilder $transportBuilder,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Mirasvit\Helpdesk\Helper\Email $helpdeskEmail,
        \Mirasvit\Helpdesk\Helper\Field $fieldHelper,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Email\Model\TemplateFactory $emailTemplateFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->variableObjectFactory = $variableObjectFactory;
        $this->translit = $translit;
        $this->userFactory = $userFactory;
        $this->ticketCollectionFactory = $ticketCollectionFactory;
        $this->departmentFactory = $departmentFactory;
        $this->fieldFactory = $fieldFactory;
        $this->transportBuilder = $transportBuilder;
        $this->config = $config;
        $this->inlineTranslation = $inlineTranslation;
        $this->helpdeskEmail = $helpdeskEmail;
        $this->fieldHelper = $fieldHelper;
        $this->context = $context;
        $this->productMetadata = $productMetadata;
        $this->storeManager = $storeManager;
        $this->assetRepo = $assetRepo;
        $this->filesystem = $filesystem;
        $this->design = $design;
        $this->auth = $auth;
        $this->emailTemplateFactory = $emailTemplateFactory;
        $this->objectManager = $objectManager;
        $this->scopeConfig = $context->getScopeConfig();

        parent::__construct($context);
    }

    //http://www.iana.org/assignments/auto-submitted-keywords/auto-submitted-keywords.xhtml
    const FLAG_AUTO_GENERATED = 'auto-generated';

    const XML_PATH_DESIGN_EMAIL_LOGO = 'design/email/logo';
    const XML_PATH_DESIGN_EMAIL_LOGO_ALT = 'design/email/logo_alt';

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    protected function getSender()
    {
        return 'general';
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket  $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param \Magento\User\Model\User         $user
     * @return void
     */
    protected function notifyUser($ticket, $customer, $user)
    {
        $ticket->setMessageType(Config::USER);
        $storeId = $ticket->getStoreId();
        if ($ticket->getUserId()) {
            $user = $this->userFactory->create();
            $user->load($ticket->getUserId());

            $this->mail(
                $ticket,
                $customer,
                $user,
                $user->getEmail(),
                $user->getName(),
                $this->getConfig()->getNotificationStaffNewMessageTemplate($storeId),
                $ticket->getLastMessage()->getAttachments()
            );
        } elseif ($department = $ticket->getDepartment()) {
            if ($department->getNotificationEmail()) {
                $this->mail(
                    $ticket,
                    $customer,
                    $user,
                    $department->getNotificationEmail(),
                    $department->getName(),
                    $this->getConfig()->getNotificationStaffNewMessageTemplate($storeId),
                    $ticket->getLastMessage()->getAttachments()
                );
            }
            if ($department->getIsMembersNotificationEnabled()) {

                foreach ($department->getUsers() as $member) {

                    $this->mail(
                        $ticket,
                        $customer,
                        $user,
                        $member->getEmail(),
                        $department->getName(),
                        $this->getConfig()->getNotificationStaffNewMessageTemplate($storeId),
                        $ticket->getLastMessage()->getAttachments()
                    );
                }
            }
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket  $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param \Magento\User\Model\User         $user
     * @return void
     */
    protected function notifyCustomer($ticket, $customer, $user)
    {
        $storeId = $ticket->getStoreId();
        $this->mail(
            $ticket,
            $customer,
            $user,
            $ticket->getCustomerEmail(),
            $ticket->getCustomerName(),
            $this->getConfig()->getNotificationNewMessageTemplate($storeId),
            $ticket->getLastMessage()->getAttachments()
        );
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket  $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param \Magento\User\Model\User         $user
     * @return void
     */
    protected function notifyThird($ticket, $customer, $user)
    {
        $storeId = $ticket->getStoreId();
        $this->mail(
            $ticket,
            $customer,
            $user,
            $ticket->getThirdPartyEmail(),
            '',
            $this->getConfig()->getNotificationThirdNewMessageTemplate($storeId),
            $ticket->getLastMessage()->getAttachments()
        );
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return void
     */
    public function notifyUserAssign($ticket)
    {
        $ticket->setMessageType(Config::USER);
        $storeId = $ticket->getStoreId();
        $attachments = $ticket->getMessages()->count() ? $ticket->getLastMessage()->getAttachments() : [];
        if ($ticket->getUserId()) {
            $user = $this->userFactory->create();
            $user->load($ticket->getUserId());
            $this->mail(
                $ticket,
                $ticket->getCustomer(),
                $user,
                $user->getEmail(),
                $user->getName(),
                $this->getConfig()->getNotificationStaffAssignTicketTemplate($storeId),
                $attachments
            );
        }
    }

    /**
     * Send email notification about creation of new ticket.
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket  $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param \Magento\User\Model\User         $user
     * @param string                           $triggeredBy
     * @param string                           $messageType
     * @return void
     */
    public function newTicket($ticket, $customer, $user, $triggeredBy, $messageType)
    {
        $storeId = $ticket->getStoreId();
            $this->mail(
                $ticket,
                $customer,
                $user,
                $ticket->getCustomerEmail(),
                $ticket->getCustomerName(),
                $this->getConfig()->getNotificationNewTicketTemplate($storeId)
            );

        if ($triggeredBy == Config::CUSTOMER) {
            if ($department = $ticket->getDepartment()) {
                if ($department->getNotificationEmail()) {
                    $this->mail(
                        $ticket,
                        $customer,
                        $user,
                        $department->getNotificationEmail(),
                        $department->getName(),
                        $this->getConfig()->getNotificationStaffNewTicketTemplate($storeId),
                        $ticket->getLastMessage()->getAttachments()
                    );
                }
                if ($department->getIsMembersNotificationEnabled()) {
                    foreach ($department->getUsers() as $member) {
                        $this->mail(
                            $ticket,
                            $customer,
                            $member,
                            $member->getEmail(),
                            $department->getName(),
                            $this->getConfig()->getNotificationStaffNewTicketTemplate($storeId),
                            $ticket->getLastMessage()->getAttachments()
                        );
                    }
                }
            }
        } else {
            $this->newMessage($ticket, $customer, $user, $triggeredBy, $messageType);
        }

        $this->getRuleEvent()->newEvent(Config::RULE_EVENT_NEW_TICKET, $ticket);
    }

    /**
     * Send email notification about new message in the ticket.
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket  $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param \Magento\User\Model\User         $user
     * @param string                           $triggeredBy
     * @param string                           $messageType
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function newMessage($ticket, $customer, $user, $triggeredBy, $messageType)
    {
        if ($messageType == Config::MESSAGE_PUBLIC) {
            if ($triggeredBy == Config::CUSTOMER) {
                $this->notifyUser($ticket, $customer, $user);
                $this->getRuleEvent()->newEvent(Config::RULE_EVENT_NEW_CUSTOMER_REPLY, $ticket);
            } elseif ($triggeredBy == Config::USER) {
                $this->notifyCustomer($ticket, $customer, $user);
                $this->getRuleEvent()->newEvent(Config::RULE_EVENT_NEW_STAFF_REPLY, $ticket);
            }
        } elseif ($messageType == Config::MESSAGE_PUBLIC_THIRD ||
            $messageType == Config::MESSAGE_INTERNAL_THIRD
        ) {
            if ($triggeredBy == Config::THIRD) {
                $this->notifyUser($ticket, $customer, $user);
                $this->getRuleEvent()->newEvent(Config::RULE_EVENT_NEW_THIRD_REPLY, $ticket);
            } elseif ($triggeredBy == Config::USER) {
                if ($messageType == Config::MESSAGE_INTERNAL_THIRD) {
                    $ticket->setAllowSendInternal(true);
                    $this->getRuleEvent()->newEvent(Config::RULE_EVENT_NEW_STAFF_REPLY, $ticket);
                }
                $this->notifyThird($ticket, $customer, $user);
            }
        } elseif ($messageType == Config::MESSAGE_INTERNAL) {
            /** @var \Magento\User\Model\User|null $currentUser */
            $currentUser = $this->auth->getUser();
            $currentUserId = $currentUser ? (int)$currentUser->getId() : 0;
            if ($ticket->getUserId() == 0 || (int)$ticket->getUserId() !== $currentUserId) {
                $ticket->setAllowSendInternal(true);

                $this->notifyUser($ticket, $customer, $user);
            }
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return void
     */
    public function sendNotificationReminder($ticket)
    {
        $templateName = $this->getConfig()->getNotificationReminderTemplate();
        $recipientEmail = $ticket->getFpRemindEmail();
        $recipientName = '';
        $config = $this->config;
        if ($config->getDeveloperIsActive()) {
            if ($sandboxEmail = $config->getDeveloperSandboxEmail()) {
                $recipientEmail = $sandboxEmail;
            }
        }

        $ticket = $this->ticketCollectionFactory->create()
            ->joinFields()
            ->addFieldToFilter('ticket_id', $ticket->getId())
            ->getFirstItem();
        $department = $this->departmentFactory->create()->load($ticket->getDepartmentId());
        $customer = $ticket->getCustomer();
        $user = $this->userFactory->create()->load($ticket->getUserId());
        $variables = [
            'ticket'     => $ticket,
            'customer'   => $customer,
            'user'       => $user,
            'department' => $department,
        ];
        $this->mail(
            $ticket,
            $ticket->getCustomer(),
            $user,
            $recipientEmail,
            $recipientName,
            $templateName,
            [],
            $variables,
            true
        );

    }


    /**
     * @param \Mirasvit\Helpdesk\Model\Satisfaction $satisfaction
     *
     * @return bool
     */
    public function sendNotificationStaffNewSatisfaction($satisfaction)
    {
        $templateName = $this->getConfig()->getNotificationStaffNewSatisfactionTemplate();
        if ($templateName == 'none') {
            return false;
        }
        if (!$ticket = $satisfaction->getTicket()) {
            return false;
        };
        if (!$user = $ticket->getUser()) {
            return false;
        }
        $variables = [];
        $variables['satisfaction'] = $satisfaction;
        $variables['ticket'] = $ticket;
        $variables['user'] = $user;

        $variables['rate_image_url'] = $this->assetRepo->getUrl(
            'Mirasvit_Helpdesk::images/smile/' . $satisfaction->getRate() . '.png',
            ['_area' => 'frontend']
        );

        $storeId = $ticket->getStoreId();

        if ($this->getConfig()->getSatisfactionIsSendResultsOwner($storeId)) {
            $recipientEmail = $user->getEmail();
            $recipientName = $user->getFirstname() . ' ' . $user->getLastname();

            $this->mail(
                $ticket,
                $ticket->getCustomer(),
                $user,
                $recipientEmail,
                $recipientName,
                $templateName,
                [],
                $variables
            );
        }
        if ($emails = $this->getConfig()->getSatisfactionResultsEmail($storeId)) {
            foreach ($emails as $recipientEmail) {
                $recipientName = '';
                $this->mail(
                    $ticket,
                    $ticket->getCustomer(),
                    $user,
                    $recipientEmail,
                    $recipientName,
                    $templateName,
                    [],
                    $variables
                );
            }
        }

        return true;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket        $ticket
     * @param \Magento\Customer\Model\Customer|\Magento\Framework\DataObject|false $customer
     * @param \Magento\User\Model\User|false         $user
     * @param string|array                           $recipientEmail
     * @param string                                 $recipientName
     * @param string                                 $templateName
     * @param iterable<\Mirasvit\Helpdesk\Model\Attachment> $attachments
     * @param array                                  $variables
     * @param bool                                   $isFollowUp
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function mail(
        $ticket,
        $customer,
        $user,
        $recipientEmail,
        $recipientName,
        $templateName,
        $attachments = [],
        $variables = [],
        $isFollowUp = false
    ) {
        if ($templateName == 'none' || $ticket->getIsMigration()) {
            return false;
        }

        $storeId = $ticket->getStoreId();
        $config = $this->config;
        if ($config->getDeveloperIsActive($storeId)) {
            if ($sandboxEmail = $config->getDeveloperSandboxEmail($storeId)) {
                $recipientEmail = $sandboxEmail;
            }
        }
        // The department carries the sender name and address, so there is no mail to send without
        // one - the same outcome as the missing-sender check further down, reached without fataling.
        /** @var \Mirasvit\Helpdesk\Model\Department|false $department */
        $department = $ticket->getDepartment();
        if (!$department) {
            return false;
        }
        $department->setData('name', $department->getName());

        // Status and priority only supply template variables and are both legitimately absent:
        // Ticket::updateFields() clears priority_id when the configured default is not a live
        // priority. Flatten the store-view name when there is a row, leave the variable empty when
        // there is not.
        /** @var \Mirasvit\Helpdesk\Model\Status|false $status */
        $status = $ticket->getStatus();
        if ($status) {
            $status->setData('name', $status->getName());
        }
        /** @var \Mirasvit\Helpdesk\Model\Priority|false $priority */
        $priority = $ticket->getPriority();
        if ($priority) {
            $priority->setData('name', $priority->getName());
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = $ticket->getStore();

        if (!$customer) {
            $customer = $ticket->getCustomer();
        }
        if (!$user) {
            $user = $ticket->getUser();
        }

        $this->preprareCustomFieldsLabels($ticket);

        $ticketVariable = $this->variableObjectFactory->create();
        $ticketVariable->setCoreObject($ticket);

        $customerVariable = $this->variableObjectFactory->create();
        $customerVariable->setCoreObject($customer);

        $userVariable = $this->variableObjectFactory->create();
        $userVariable->setCoreObject($user);

        $departmentVariable = $this->variableObjectFactory->create();
        $departmentVariable->setCoreObject($department);
        $user = $user ? $userVariable : $user;
        $variables = array_merge(
            $variables,
            [
                'ticket'               => $ticketVariable,
                'ticket_id'            => $ticket->getId(),
                'customer'             => $customerVariable,
                'user'                 => $user, //user is not required for ticket, userVariable could be used only for the existing user
                'department'           => $departmentVariable,
                'store'                => $store, // do not override store due to strict types in Magento
                'preheader_text'       => "",//leave for compatibility with old version.
                'email_preheader_text' => $this->helpdeskEmail->getPreheaderText($ticket->getLastMessagePlainText()),
                'hidden_separator'     => $this->helpdeskEmail->getHiddenSeparator(),
                'use_form_key'         => (bool)$this->scopeConfig->getValue('admin/security/use_form_key'),
                'is_message_internal'  => (bool)$ticket->getLastMessage()->isInternal(),
            ]
        );

        if (isset($variables['email_subject'])) {
            $variables['email_subject'] = $this->processVariable($variables['email_subject'], $variables, $storeId);
        }
        if (isset($variables['email_body'])) {
            $variables['email_body'] = $this->processVariable($variables['email_body'], $variables, $storeId);
        }
        if ($user && $user->getSignature()) {
            $user->setSignature($this->processVariable($user->getSignature(), $variables, $storeId));
        }

        // Proper sender email names and addresses for department notification
        //We use Vertical line as Gmail SMPT uses escape when sender name contains comma or semicolon && From contains a charset
        if ($this->getConfig()->getGeneralSignTicketBy($storeId) === Config::SIGN_TICKET_BY_USER && $user && $user->getName()) {
            $senderName = $store->getFrontendName() . ' | ' . $user->getName();
        } else if ($this->getConfig()->getGeneralSignTicketBy($storeId) === Config::SIGN_TICKET_BY_TEMPLATE) {
            $template = $this->getConfig()->getGeneralSignTicketTemplate($storeId);
            if ($user) {
                $template = str_replace("[user_name]", $user->getName(), $template);
            } else {
                $template = str_replace("[user_name] | ", "", $template);
                $template = str_replace("[user_name]", $department->getName(), $template);
            }
            $template = str_replace("[department_name]", $department->getName(), $template);
            $template = str_replace("[store_name]", $store->getFrontendName(), $template);
            $senderName = $template;
        } else {
            $senderName  = $store->getFrontendName() . ' | ' . $department->getName();
        }
        $senderEmail = $department->getSenderEmail();

        if (!$senderEmail) {
            return false;
        }
        if (!$recipientEmail) {
            return false;
        }
        $this->inlineTranslation->suspend();

        $this->transportBuilder
            ->setTemplateIdentifier($templateName)
            ->setTemplateOptions([
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId ?: $this->storeManager->getStore()->getId(),
            ])
            ->setTemplateVars($variables);

        foreach ($attachments as $attachment) {
            if ($attachment->getIsAllowed()) {
                $this->transportBuilder->addAttachment(
                    $attachment->getBody(),
                    $attachment->getType(),
                    \Magento\Framework\HTTP\Mime::DISPOSITION_ATTACHMENT,
                    \Magento\Framework\HTTP\Mime::ENCODING_BASE64,
                    $attachment->getName()
                );
            }
        }

        $recipientName = $this->translit->filter($recipientName);
        // m2.3.3 \Magento\Framework\Mail\Template\TransportBuilder::addAddressByType()
        // requires that first set of "To" will be string
        if (is_array($recipientEmail)) {
            $toEmail = array_pop($recipientEmail);
        } else {
            $toEmail = $recipientEmail;
            $recipientEmail = null;
        }
        $senderName = $this->translit->filter($senderName);
        $this->transportBuilder
            ->setFrom([
                'name'  => $senderName,
                'email' => $senderEmail,
            ])
            ->addTo($toEmail, $recipientName);
        if (is_array($recipientEmail) && !empty($recipientEmail)) {
            $this->transportBuilder->addTo($recipientEmail, $recipientName);
        }
        if ($ticket->getLastMessage()->getType() != Config::USER
            && $ticket->getLastMessage()->getType() != Config::MESSAGE_INTERNAL_THIRD
            && $ticket->getLastMessage()->getType() != Config::MESSAGE_INTERNAL
            && !$isFollowUp && !$ticket->getRule()
            || $ticket->getRule() && $ticket->getRule()->getIsSendUser()) {
            $this->addCc($ticket);
        }
        if (!$isFollowUp) {
            $this->addBcc($ticket);
        }
        $this->transportBuilder->addCustomHeader($this->getMstCustomHeaders($ticket, $toEmail));

        /* @var \Magento\Email\Model\Transport $transport */
        $transport = $this->transportBuilder->getTransport();

        if (version_compare($this->productMetadata->getVersion(), "2.3.0", "==")) {
            $message = $transport->getMessage();
            $message->setFrom([$senderEmail => $senderName]);
        }

        try {
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->context->getLogger()->alert($e->getMessage());
        }

        $this->inlineTranslation->resume();

        return true;
    }

    /**
     * Build the custom headers attached to every outgoing notification: the
     * threading headers plus the RFC 3834 auto-submitted markers that stop a
     * remote mailbox from auto-replying back and starting an infinite loop
     * (pm#593). Extracted from mail() so the header values are unit-testable
     * (pm#912).
     *
     * We use `Auto-Submitted: auto-generated` (not `auto-replied`): these are
     * generated notifications, not direct replies, so `auto-generated` is the
     * RFC 3834-correct value. It still suppresses auto-replies (RFC 3834: a
     * responder must not reply to any Auto-Submitted value other than "no"),
     * while mailing-list managers such as Google Groups drop `auto-replied` as
     * a suspected auto-response and silently discard the message (pm#912).
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @param string                          $toEmail
     *
     * @return array<string, string>
     */
    protected function getMstCustomHeaders($ticket, $toEmail)
    {
        return [
            'In-Reply-To' => $ticket->getCode() . $toEmail,
            'References' => $ticket->getCode() . $toEmail,
            //minimize the number of useless emails we get in response as outlook adds X-Auto-Response-Suppress header to regular emails
            'X-Auto-Response-Suppress' => 'All',
            'Auto-Submitted' => self::FLAG_AUTO_GENERATED,
        ];
    }

    /**
     * Add bcc to transport builder
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return void
     */
    private function addBcc($ticket)
    {
        // m2.3.3 \Magento\Framework\Mail\Template\TransportBuilder::addAddressByType()
        // requires that first set of "bcc" will be a string
        $bcc = array_unique(array_merge(
            $this->getConfig()->getGeneralBccEmail($ticket->getStoreId()), $ticket->getBcc()
        ));
        $bcc = $this->helpdeskEmail->stripGatewayEmails($bcc);
        if (is_array($bcc)) {
            $bccEmail = array_pop($bcc);
        } else {
            $bccEmail = $bcc;
            $bcc = null;
        }
        if (is_string($bccEmail) && !empty($bccEmail)) {
            $this->transportBuilder->addBcc($bccEmail);
        }
        if (is_array($bcc) && !empty($bcc)) {
            $this->transportBuilder->addBcc($bcc);
        }
    }

    /**
     * Add cc to transport builder
     *
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return void
     */
    private function addCc($ticket)
    {
        // m2.3.3 \Magento\Framework\Mail\Template\TransportBuilder::addAddressByType()
        // requires that first set of "cc" will be a string
        $cc = $this->helpdeskEmail->stripGatewayEmails($ticket->getCc());
        if (is_array($cc) && !empty($cc)) {
            $ccEmail = array_pop($cc);
        } else {
            $ccEmail = $cc;
            $cc = null;
        }
        if (is_string($ccEmail) && !empty($ccEmail)) {
            $this->transportBuilder->addCc($ccEmail);
        }
        if (is_array($cc) && !empty($cc)) {
            $cc = array_slice($cc, 0, self::LIMIT_MAX_CC); // limit to prevent spam

            $this->transportBuilder->addCc($cc);
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return void
     */
    protected function preprareCustomFieldsLabels($ticket)
    {
        $data = $ticket->getData();
        $customFields = [];
        foreach ($data as $k => $v) {
            if (strpos((string) $k, 'f_') === 0) {
                $customFields[] = $k;
            }
        }
        $collection = $this->fieldFactory->create()->getCollection()
            ->addFieldToFilter('type', \Mirasvit\Helpdesk\Model\Field::TYPE_SELECT)
            ->addFieldToFilter('code', ['in' => $customFields]);
        ;
        foreach ($collection as $field) {
            $value = $this->fieldHelper->getValue($ticket, $field);
            $ticket->setData($field->getCode().'_label', $value);
        }
    }

    /**
     * Can parse template and return ready text.
     *
     * @param string $variable  Text with variables like {{var customer.name}}.
     * @param array  $variables Array of variables.
     * @param int    $storeId
     *
     * @return string - ready text
     */
    public function processVariable($variable, $variables, $storeId)
    {
        $template = $this->emailTemplateFactory->create();
        $template->setDesignConfig([
            'area'  => 'frontend',
            'store' => $storeId,
        ]);
        $template->setTemplateText($variable);
        $html = $template->getProcessedTemplate($variables);

        return $html;
    }

    /**
     * @return \Mirasvit\Helpdesk\Helper\Ruleevent
     */
    protected function getRuleEvent()
    {
        return $this->objectManager->get('\Mirasvit\Helpdesk\Helper\Ruleevent');
    }
}

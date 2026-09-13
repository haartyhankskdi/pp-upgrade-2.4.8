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

require_once dirname(__FILE__) . "/../../lib/Mirasvit/Ddeboer/Imap/load.php";

/**
 * Class Fetch.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Fetch extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    private $context;
    /**
     * @var \Mirasvit\Helpdesk\Model\AttachmentFactory
     */
    private $attachmentFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Email\CollectionFactory
     */
    private $emailCollectionFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\EmailFactory
     */
    private $emailFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\GatewayFactory
     */
    private $gatewayFactory;
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Mirasvit\Helpdesk\Helper\Oauth2
     */
    private $oauth2Helper;

    /**
     * @var \Mirasvit\Helpdesk\Helper\MicrosoftGraph
     */
    private $microsoftGraph;

    /**
     * Fetch constructor.
     * @param \Mirasvit\Helpdesk\Model\GatewayFactory $gatewayFactory
     * @param \Mirasvit\Helpdesk\Model\EmailFactory $emailFactory
     * @param \Mirasvit\Helpdesk\Model\AttachmentFactory $attachmentFactory
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Email\CollectionFactory $emailCollectionFactory
     * @param Config $config
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mirasvit\Helpdesk\Helper\Oauth2 $oauth2Helper
     * @param \Mirasvit\Helpdesk\Helper\MicrosoftGraph $microsoftGraph
     */
    public function __construct(
        \Mirasvit\Helpdesk\Model\GatewayFactory $gatewayFactory,
        \Mirasvit\Helpdesk\Model\EmailFactory $emailFactory,
        \Mirasvit\Helpdesk\Model\AttachmentFactory $attachmentFactory,
        \Mirasvit\Helpdesk\Model\ResourceModel\Email\CollectionFactory $emailCollectionFactory,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Magento\Framework\App\Helper\Context $context,
        \Mirasvit\Helpdesk\Helper\Oauth2 $oauth2Helper,
        \Mirasvit\Helpdesk\Helper\MicrosoftGraph $microsoftGraph
    ) {
        $this->gatewayFactory         = $gatewayFactory;
        $this->emailFactory           = $emailFactory;
        $this->attachmentFactory      = $attachmentFactory;
        $this->emailCollectionFactory = $emailCollectionFactory;
        $this->config                 = $config;
        $this->context                = $context;
        $this->oauth2Helper           = $oauth2Helper;
        $this->microsoftGraph         = $microsoftGraph;

        parent::__construct();
    }

    /**
     * @var \Mirasvit\Helpdesk\Model\Gateway
     */
    protected $gateway;

    /**
     * @var  \Mirasvit_Ddeboer_Imap_Connection|null
     */
    protected $connection;

    /**
     * @var  \Mirasvit_Ddeboer_Imap_Mailbox|\Mirasvit\Helpdesk\Helper\GmailMailboxAdapter|\Mirasvit\Helpdesk\Helper\MicrosoftGraphMailboxAdapter|null
     */
    protected $mailbox;

    /**
     * @return string
     */
    public function isDev()
    {
        return $this->config->getDeveloperIsActive();
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Gateway $gateway
     *
     * @return bool
     */
    public function connect($gateway)
    {
        $this->validate();

        $this->gateway = $gateway;

        $authType = $gateway->getAuthorizationType();

        // Gmail OAuth2 uses the Google API client (existing behavior).
        if ($authType === \Mirasvit\Helpdesk\Model\Config\Source\AuthorizationType::TYPE_OAUTH2) {
            return $this->connectOAuth2($gateway);
        }

        // Microsoft 365 / Exchange Online OAuth2 authenticates over IMAP using the XOAUTH2 SASL string.
        if ($authType === \Mirasvit\Helpdesk\Model\Config\Source\AuthorizationType::TYPE_OAUTH2_MICROSOFT) {
            return $this->connectMicrosoftOAuth2($gateway);
        }

        // IMAP basic auth (existing behavior)
        $flags = sprintf('/%s', $gateway->getProtocol());
        if ($gateway->getEncryption() == 'ssl') {
            $flags .= '/ssl';
        }
        $flags .= '/novalidate-cert';

        $server = new \Mirasvit_Ddeboer_Imap_Server($gateway->getHost(), $gateway->getPort(), $flags);
        if (function_exists('imap_timeout')) {
            imap_timeout(1, 20);
        }
        if (!$this->connection = $server->authenticate($gateway->getLogin(), $gateway->getPassword())) {
            return false;
        }

        $mailboxes = $this->connection->getMailboxNames();
        if (trim($gateway->getFolder()) && in_array($gateway->getFolder(), $mailboxes)) {
            $mailboxName = $gateway->getFolder();
        } else {
            if (in_array('INBOX', $mailboxes)) {
                $mailboxName = 'INBOX';
            } elseif (in_array('Inbox', $mailboxes)) {
                $constantInbox = 'Inbox';
                $mailboxName = $constantInbox;
            } else {
                $mailboxName = $mailboxes[0];
            }
        }

        $this->mailbox = $this->connection->getMailbox($mailboxName);

        return true;
    }

    protected function connectOAuth2($gateway)
    {

        try {
            // Get access token
            $accessToken = $this->oauth2Helper->ensureValidAccessToken($gateway);

            if (!$accessToken) {
                throw new \Exception('Empty or invalid access token');
            }

            // Google Client
            $client = new \Google\Client();
            $client->setAccessToken([
                'access_token' => $accessToken,
                'created' => time(),
                'expires_in' => 3600,
            ]);

            // Gmail service
            $gmailService = new \Google\Service\Gmail($client);

            $this->mailbox = new GmailMailboxAdapter($gmailService, 'me');

            return true;

        } catch (\Google\Service\Exception $e) {
            if ($this->isDev()) {
                echo 'Gmail API Error: ' . $e->getMessage();
            }
            return false;

        } catch (\Exception $e) {
            if ($this->isDev()) {
                echo 'OAuth2 Connection Error: ' . $e->getMessage();
            }
            return false;
        }
    }

    /**
     * Connect to a Microsoft 365 / Exchange Online mailbox through Microsoft Graph.
     *
     * IMAP is not usable here: PHP's imap extension is built against UW c-Client 2007f, which has
     * no XOAUTH2/SASL-OAuth support, and Microsoft has disabled basic LOGIN. So - exactly as the
     * Gmail gateway fetches through the Google API rather than imap_open - the Microsoft gateway
     * fetches through Graph via a mailbox adapter that exposes the same interface as the IMAP and
     * Gmail mailboxes.
     *
     * @param \Mirasvit\Helpdesk\Model\Gateway $gateway
     * @return bool
     */
    protected function connectMicrosoftOAuth2($gateway)
    {
        try {
            $accessToken = $this->oauth2Helper->ensureValidAccessToken($gateway);

            if (!$accessToken) {
                throw new \Exception('Empty or invalid access token');
            }

            $maxMessages = (int)$gateway->getFetchLimit() ?: 100;

            $this->connection = null;
            $this->mailbox    = new MicrosoftGraphMailboxAdapter($this->microsoftGraph, $accessToken, $maxMessages);

            return true;
        } catch (\Exception $e) {
            // Always log (cron runs headless); the message now carries the underlying IMAP error.
            $this->context->getLogger()->error(
                'Helpdesk Microsoft 365 IMAP connection failed for gateway #'
                . $gateway->getId() . ': ' . $e->getMessage()
            );

            if ($this->isDev()) {
                echo 'Microsoft OAuth2 Connection Error: ' . $e->getMessage();
            }

            return false;
        }
    }


    /**
     * @return \Mirasvit_Ddeboer_Imap_Mailbox|\Mirasvit\Helpdesk\Helper\GmailMailboxAdapter|\Mirasvit\Helpdesk\Helper\MicrosoftGraphMailboxAdapter
     */
    public function getMailbox()
    {
        /** @var \Mirasvit_Ddeboer_Imap_Mailbox|\Mirasvit\Helpdesk\Helper\GmailMailboxAdapter|\Mirasvit\Helpdesk\Helper\MicrosoftGraphMailboxAdapter $mailbox */
        $mailbox = $this->mailbox;

        return $mailbox;
    }

    /**
     * @return void
     */
    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
        }

        $this->mailbox = null;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     *
     * @return string|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getFromEmail($message)
    {
        // if we have reply to, we will set it as "from", because we will not reply on it
        $fromEmail = false;
        if ($message->getReplyTo() && !is_array($message->getReplyTo()) && $message->getReplyTo()->getAddress()) {
            $fromEmail = $message->getReplyTo()->getAddress();
        } elseif (is_array($message->getReplyTo())) {
            foreach ($message->getReplyTo() as $address) {
                if ($address->mailbox && $address->host) {
                    $fromEmail = $address->mailbox . '@' . $address->host;
                }
            }
        } elseif ($message->getFrom() && !is_array($message->getFrom())) {
            /** @var \Mirasvit_Ddeboer_Imap_Message_EmailAddress $from */
            $from = $message->getFrom();
            $fromEmail = $from->getAddress();
        }

        return $fromEmail;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     *
     * @return string|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getFromName($message)
    {
        // if we have reply to, we will set it as "from", because we will not reply on it
        $fromName = 'unknown';
        $fromName = 'unknown';
        if ($message->getReplyTo() && !is_array($message->getReplyTo()) && $message->getReplyTo()->getName()) {
            $fromName = $message->getReplyTo()->getName();
        } elseif (is_array($message->getReplyTo())) {
            foreach ($message->getReplyTo() as $name) {
                if ($name->personal) {
                    $fromName = $name->personal;
                }
            }
        } elseif ($message->getFrom() && !is_array($message->getFrom())) {
            /** @var \Mirasvit_Ddeboer_Imap_Message_EmailAddress $from */
            $from = $message->getFrom();
            $fromName = $from->getName();
        }

        return $fromName;
    }

    /**
     * @return array
     */
    public function getGatewayEmails()
    {
        $collection = $this->gatewayFactory->create()->getCollection()->addFieldToFilter('is_active', ['eq' => 1]);

        $emails = array();
        if ($collection->count()) {
            /** @var \Mirasvit\Helpdesk\Model\Gateway $gateway */
            foreach ($collection as $gateway) {
                $emails[] = $gateway->getEmail();
            }
        }
        return $emails;

    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     *
     * @return \Mirasvit\Helpdesk\Model\Email
     *
     */
    public function createEmail($message)
    {
        $format = $this->getMessageFormat($message);
        $body = $this->getMessageBody($message, $format);

        $to = $this->getTo($message);
        $cc = $this->getCc($message);
        $headers = $this->getHeaders($message);
        $fromEmail = $this->getFromEmail($message);
        $senderName = $this->getFromName($message);

        $mailingDate = (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        if ($message->getDate()) {
            try {
                $date = new \DateTime($message->getDate());
                $mailingDate = $date->getTimestamp();
            } catch (\Exception $e) {}
        }

        $email = $this->emailFactory->create()
            ->setMessageId($message->getId())
            ->setFromEmail($fromEmail)
            ->setSenderName($senderName)
            ->setToEmail(implode(',', $to))
            ->setCc(implode(', ', $cc))
            ->setSubject($message->getSubject())
            ->setMailingDate($mailingDate)
            ->setBody($body)
            ->setFormat($format)
            ->setHeaders($headers)
            ->setIsProcessed($message->getIsProcessed() ?: false);
        return $email;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     *
     * @return \Mirasvit\Helpdesk\Model\Email|false
     *
     */
    public function saveEmail($message)
    {
        if ($this->isMessageFetched($message)) {
            return false;
        }
        $email = $this->createEmail($message);

        if ($this->gateway) { //may be null during tests
            $email->setGatewayId($this->gateway->getId());
        }

        $gateways = $this->getGatewayEmails();
        $fromEmail = $this->getFromEmail($message);

        // Recorded even when the message is fetched into a ticket: Process::processEmail() keeps
        // auto-replies from opening tickets and Ticket::addMessage() keeps them from triggering
        // notifications and rules, both of which would otherwise auto-reply to an auto-reply and
        // loop against any responder that ignores our RFC 3834 suppression headers.
        $isAutosubmitted = $this->isMessageAutosubmitted($message);
        $email->setIsAutosubmitted($isAutosubmitted);

        // Gateway-to-gateway mail is always suppressed - that is mail-loop protection and
        // is not configurable. Auto-responses are only suppressed while the admin has not
        // asked for them to be fetched into tickets.
        $isSuppressed = in_array($fromEmail, $gateways)
            || ($isAutosubmitted && !$this->isFetchAutorepliesEnabled());

        if ($email->getIsProcessed() === false && $isSuppressed) {
            $email->setIsProcessed(true);
            $email->save();

            return $email;
        }

        $email->save();
        $this->saveAttachments($email, $message);

        return $email;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @param int                            $format
     * @return string
     */
    protected function getMessageBody($message, $format)
    {
        if ($format == Config::FORMAT_PLAIN) {
            $body = $message->getBodyText();
        } else {
            $body = $message->getBodyHtml();
            if (empty($body)) { //html can be even in plain body
                $body = $message->getBodyText();
            }
        }
        $bodySizeLimit = ($format == Config::FORMAT_HTML) ? 1000000 : 10000;

        if (strlen($body) > $bodySizeLimit) {
            $body = substr($body, 0, $bodySizeLimit);
        }

        $body = $this->removeGoogleServiceTags($body);

        return $body;
    }

    /**
     * Google insert tag <wbr> for all long words. Our guest ID is long word
     *
     * @param string $body
     * @return string
     */
    private function removeGoogleServiceTags($body)
    {
        return str_replace('<wbr>', '', $body);
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @return int
     */
    protected function getMessageFormat($message)
    {
        $bodyHtml = $message->getBodyHtml();
        $bodyPlain = $message->getBodyText();
        if (empty($bodyHtml)) {
            $format = Config::FORMAT_PLAIN;
            $tags = ['<div', '<br', '<tr'];
            foreach ($tags as $tag) {
                if (stripos($bodyPlain, $tag) !== false) {
                    $format = Config::FORMAT_HTML;
                    break;
                }
            }
        } else {
            $format = Config::FORMAT_HTML;
        }
        return $format;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @return string
     */
    protected function getHeaders($message)
    {
        $headersObj = $message->getHeaders();

        if (is_object($headersObj) && method_exists($headersObj, 'toString')) {
            $headers = $headersObj->toString(); // старі IMAP об’єкти
        } elseif (is_string($headersObj)) {
            $headers = $headersObj; // GmailMessageAdapter вже повертає рядок
        } else {
            $headers = ''; // на всяк випадок
        }

        // обмеження на 10000 символів
        if (strlen($headers) > 10000) {
            $headers = substr($headers, 0, 10000);
        }

        return $headers;
    }


    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @return array
     */
    protected function getTo($message)
    {
        $to = [];
        foreach ($message->getTo() as $email) {
            $to[] = $email->getAddress();
        }
        return $to;
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @return array
     */
    protected function getCc($message)
    {
        $cc = [];
        foreach ($message->getCc() as $copy) {
            $cc[] = $copy->mailbox . '@' . $copy->host;
        }
        return $cc;
    }

    /**
     * Whether the message is an automatic response - an out-of-office/vacation reply,
     * mailing-list mail or a delivery bounce.
     *
     * Pure header predicate: it answers "what is this message", never "what should we do
     * with it". The fetch/ignore decision belongs to saveEmail(), which is the only place
     * with the gateway (and therefore the config scope) at hand.
     *
     * This is the single source of truth for both transports. Mirasvit_Ddeboer_Imap_Message
     * no longer reports auto-responses as fetch errors, so IMAP and Gmail gateways - the
     * latter never populated getErrors() at all - now classify mail identically.
     *
     * Per RFC 3834, an automatic response must not be sent to a message that is itself
     * auto-generated, which is why such mail is suppressed by default.
     * http://www.iana.org/assignments/auto-submitted-keywords/auto-submitted-keywords.xhtml
     *
     * @param \Mirasvit_Ddeboer_Imap_Message|\Mirasvit\Helpdesk\Helper\GmailMessageAdapter $message
     * @return bool
     */
    protected function isMessageAutosubmitted($message)
    {
        $raw = $message->getHeaders()->toString();
        $headerBlock = $this->getHeaderBlock($raw);

        // Header patterns are matched against the header block only, and anchored to the
        // start of a line. toString() returns headers *and* body, so an unanchored search
        // also matches quoted headers inside a reply - which silently swallowed genuine
        // customer replies that quoted one of our own notifications (those carry
        // Auto-Submitted: auto-generated, see Notification::getMstCustomHeaders()).
        //
        // Headers deliberately left out:
        //  - X-BeenThere: does not work for om-ga mail.
        //  - X-Auto-Response-Suppress: set by Microsoft Exchange on *regular* mail too, only
        //    to stop other Exchange servers from auto-replying, so it proves nothing here.
        //    https://www.jitbit.com/maxblog/18-detecting-outlook-autoreplyout-of-office-emails-and-x-auto-response-suppress-header/
        if (preg_match('/^Auto-Submitted:\s*(auto-replied|auto-generated|auto-notified)/im', $headerBlock) === 1
            // RFC 3834 / common practice: bulk, junk, list and auto_reply precedence all
            // mark mail that must not receive an auto-reply.
            || preg_match('/^Precedence:\s*(bulk|junk|list|auto_reply)/im', $headerBlock) === 1
            // Vacation responders and bounce reports announce themselves by header name.
            || preg_match('/^(X-Autoreply|X-Autorespond|X-Failed-Recipients):/im', $headerBlock) === 1
        ) {
            return true;
        }

        // Delivery status notifications (bounces) must not be replied to. A real bounce is a
        // multipart/report whose delivery-status lives in a MIME part, not in the top-level
        // headers, so this one is matched against the whole message.
        return stripos($raw, 'message/delivery-status') !== false;
    }

    /**
     * Header block of a raw message - everything before the first empty line.
     *
     * Returns the input unchanged when there is no body separator, which is the case for
     * GmailMessageAdapter (it serialises headers only).
     *
     * @param string $raw
     * @return string
     */
    private function getHeaderBlock($raw)
    {
        // strpos() rather than preg_split(): the latter is typed list<string>|false, so it would
        // need a false-check for a failure a literal pattern cannot produce. CRLF is checked first
        // because "\n\n" is not a substring of "\r\n\r\n" - on a CRLF message the real boundary is
        // therefore always found before any bare-LF pair inside the body.
        foreach (["\r\n\r\n", "\n\n", "\r\r"] as $separator) {
            $position = strpos($raw, $separator);
            if ($position !== false) {
                return substr($raw, 0, $position);
            }
        }

        return $raw;
    }

    /**
     * Whether auto-responses should be fetched into tickets for the gateway being processed.
     *
     * The store must be passed explicitly: fetching runs from cron, where the current store
     * is the admin store, so a website- or store-view-scoped value would be invisible and
     * the config.xml default would silently win.
     *
     * @return bool
     */
    private function isFetchAutorepliesEnabled()
    {
        $storeId = null;

        // Guarded with `if` rather than a ternary to match saveEmail()'s sibling guard: the
        // property is doc-typed non-null, so PHPStan proves either form constant, and only
        // if.alwaysTrue is in the Fetch.php ignore list as an author-intended guard.
        if ($this->gateway) { //may be null during tests
            $storeId = $this->gateway->getStoreId();
        }

        return $this->config->getGeneralFetchAutoreplies($storeId);
    }

    /**
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     * @return bool
     */
    protected function isMessageFetched($message)
    {
        $emails = $this->emailCollectionFactory->create()
            ->addFieldToFilter('message_id', $message->getId())
            ->addFieldToFilter('from_email', (string)$this->getFromEmail($message));

        if ($emails->count()) {
            return true;
        }
        return false;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Email $email
     * @param \Mirasvit_Ddeboer_Imap_Message $message
     *
     * @return void
     */
    protected function saveAttachments($email, $message)
    {
        $attachments = $message->getAttachments();

        if ($attachments) {
            foreach ($attachments as $a) {
                $attachment = $this->attachmentFactory->create();
                $attachment->setName($a->getFilename())
                    ->setType($a->getType())
                    ->setSize($a->getSize())
                    ->setEmailId($email->getId())
                    ->setBody($a->getDecodedContent())
                    ->save();
            }
        }
    }

    /**
     * @return void
     * @throws \Mirasvit_Ddeboer_Imap_Exception_MessageDeleteException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function fetchEmails()
    {
        $msgs = 0;
        $max = $this->gateway->getFetchMax();

        $mailbox = $this->mailbox;
        if (!$mailbox) {
            return;
        }

        $messages = $mailbox->getMessages('UNSEEN');
        $emailsNumber = $mailbox->count();

        if ($limit = $this->gateway->getFetchLimit()) {
            $start = $emailsNumber - $limit + 1;
            if ($start < 1) {
                $start = 1;
            }
            for ($num = $start; $num <= $emailsNumber; ++$num) {
                try { // we can have different errors during fetching of email.
                    // we don't want to stop fetching of all queue.
                    $message = $mailbox->getMessage($num);
                    if (!$message) {
                        continue;
                    }
                    if ($message->getErrors()) { // we do not want log imap errors
                        continue; // genuine failures only, see the note in the branch below
                    }
                    if ($this->saveEmail($message)) {
                        if ($message instanceof GmailMessageAdapter
                            || $message instanceof MicrosoftGraphMessageAdapter) {
                            $message->markAsRead();
                        }
                        if ($this->gateway->getIsDeleteEmails()) {
                            $message->delete();
                            $mailbox->expunge();
                        }
                        ++$msgs;
                    }
                    if ($max && $msgs >= $max) {
                        break;
                    }
                } catch (\Exception $e) {
                    $this->context->getLogger()->error($e->getMessage());
                }
            }
        } else {
            foreach ($messages as $message) {
                // getErrors() now reports genuine IMAP/parse failures only - auto-responses are
                // classified by isMessageAutosubmitted() and must reach saveEmail(). The Gmail
                // adapter has always returned an empty list here, so both transports share this.
                if ($message->getErrors()) {
                    continue;
                }
                try { //we can have different errors during fetching of email.
                    // we don't want to stop fetching of all queue.
                    if ($this->saveEmail($message)) {
                        if ($message instanceof GmailMessageAdapter
                            || $message instanceof MicrosoftGraphMessageAdapter) {
                            $message->markAsRead();
                        }
                        if ($this->gateway->getIsDeleteEmails()) {
                            $message->delete();
                            $mailbox->expunge();
                        }
                        ++$msgs;
                    }
                    if ($max && $msgs >= $max) {
                        break;
                    }
                } catch (\Exception $e) {
                    $this->context->getLogger()->error($e->getMessage());
                }
            }
        }
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Gateway $gateway
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return bool
     */
    public function fetch($gateway)
    {
        $this->validate();

        if (!$this->connect($gateway)) {
            return false;
        }
        $this->fetchEmails();
        $this->close();

        return true;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return bool
     */
    public function validate()
    {
        if (!function_exists('imap_open')) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Can't fetch.
                Please, ask your hosting provider to enable the IMAP extension in PHP configuration of your server.")
            );
        }

        return true;
    }
}

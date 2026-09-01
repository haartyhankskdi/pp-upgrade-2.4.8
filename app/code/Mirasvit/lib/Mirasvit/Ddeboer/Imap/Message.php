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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


// @codingStandardsIgnoreFile
// namespace Mirasvit_Ddeboer\Imap;

// use Mirasvit_Ddeboer\Imap\Message\EmailAddress;
// use Mirasvit_Ddeboer\Imap\Exception\MessageDeleteException;
// use Mirasvit_Ddeboer\Imap\Exception\MessageMoveException;

/**
 * An IMAP message (e-mail).
 */
class Mirasvit_Ddeboer_Imap_Message extends Mirasvit_Ddeboer_Imap_Message_Part
{
    /**
     * @var int
     */
    private $isProcessed = 0;
    private $flagHeaders;

    /**
     * @var resource
     */
    protected $stream;
    /**
     * @var
     */
    protected $headers;
    /**
     * @var
     */
    protected $attachments;

    /**
     * @var bool
     */
    protected $keepUnseen = false;

    /**
     * @var bool
     */
    protected $errors = false;

    /**
     * Constructor.
     *
     * @param \resource $stream        IMAP stream
     * @param int       $messageNumber Message number
     */
    public function __construct($stream, $messageNumber)
    {
        set_error_handler([$this, 'saveErroredEmails']);
        $this->stream = $stream;
        $this->messageNumber = $messageNumber;

        $this->loadStructure();
        if (!$this->getErrors()) {
            $this->checkAutoresponse();
        }
        restore_error_handler();
    }

    /**
     * @param string $errno
     * @param string $errstr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function saveErroredEmails($errno, $errstr)
    {
        $this->setIsProcessed(2);
    }

    /**
     * @return int
     */
    public function getIsProcessed()
    {
        return $this->isProcessed;
    }

    /**
     * @param int $isProcessed
     * @return $this
     */
    public function setIsProcessed($isProcessed)
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    /**
     * Get Cc recipients.
     *
     * @return stdClass[] Empty array in case message has no CC: recipients
     */
    public function getCc()
    {
        return $this->getHeaders()->get('cc') ?: array();
    }

    /**
     * Has the message been marked as read?
     *
     * @return bool
     */
    public function isSeen()
    {
        return 'U' != $this->getFlagHeaders()->get('unseen');
    }

    /**
     * Get message id.
     *
     * A unique message id in the form <...>
     *
     * @return string
     */
    public function getId()
    {
        $id = $this->getHeaders()->get('message_id');
        if (empty($id)) { //in some emails ID is empty
            $date = $this->getDate();
            if (is_object($date)) {
                $date = $date->format('Y-m-d H:i:s');
            }
            $id = md5($date.$this->getContent());
        }

        return $id;
    }

    /**
     * Get message sender (from headers).
     *
     * @return Mirasvit_Ddeboer_Imap_Message_EmailAddress|bool
     */
    public function getFrom()
    {
        return $this->getHeaders()->get('from');
    }

    /**
     * Get message recipients (from headers).
     *
     * @return Mirasvit_Ddeboer_Imap_Message_EmailAddress[] Empty array in case message has no To: recipients
     */
    public function getTo()
    {
        return $this->getHeaders()->get('to');
    }

    /*
    * @return Mirasvit_Ddeboer_Imap_Message_EmailAddress[] Empty array in case message has no To: recipients
    */
    /**
     * @return mixed|null
     */
    public function getReplyTo()
    {
        return $this->getHeaders()->get('reply_to');
    }

    /**
     * Get message number (from headers).
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->messageNumber;
    }

    /**
     * Get date (from headers).
     *
     * @return string
     */
    public function getDate()
    {
        return $this->getHeaders()->get('date');
    }

    /**
     * Get message size (from headers).
     *
     * @return int
     */
    public function getSize()
    {
        return $this->getHeaders()->get('size');
    }

    /**
     * Get raw part content.
     *
     * @return string
     */
    public function getContent()
    {
        // Null headers, so subsequent calls to getHeaders() will return
        // updated seen flag
        $this->headers = null;

        return $this->doGetContent($this->keepUnseen);
    }

    /**
     * Get message subject (from headers).
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->getHeaders()->get('subject');
    }

    /**
     * Get message headers.
     *
     * @return Mirasvit_Ddeboer_Imap_Message_Headers
     */
    public function getHeaders()
    {
        $this->getFlagHeaders();
        if (null === $this->headers) {
            // imap_header is much faster than imap_fetchheader
            // imap_header returns only a subset of all mail headers,
            // but it does include the message flags.
            // $headers = imap_header($this->stream, $this->messageNumber);
            // we use imap_fetchheader because imap_header does not parse correctly reply_to field
            $headers = imap_rfc822_parse_headers(imap_fetchheader($this->stream, $this->messageNumber));
            $this->headers = new Mirasvit_Ddeboer_Imap_Message_Headers($headers);
            $this->headers->raw_text = imap_fetchheader($this->stream, $this->messageNumber, FT_PREFETCHTEXT)
                .imap_body($this->stream, $this->messageNumber);
            imap_errors();
            imap_alerts();
        }

        return $this->headers;
    }

    /**
     * Get message flags headers.
     *
     * @return Mirasvit_Ddeboer_Imap_Message_Headers
     */
    public function getFlagHeaders()
    {
        if (null === $this->flagHeaders) {
            // imap_header includes the message flags.
            $headers = imap_header($this->stream, $this->messageNumber);
            $this->flagHeaders = new Mirasvit_Ddeboer_Imap_Message_Headers($headers);
            imap_errors();
            imap_alerts();
        }

        return $this->flagHeaders;
    }

    /**
     * @return bool
     */
    public function hasHtmlText()
    {
        if ($this->type == 'text' && $this->subtype == 'HTML') {
            return true;
        }
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'HTML') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasPlainText()
    {
        if ($this->type == 'text' && $this->subtype == 'PLAIN') {
            return true;
        }
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'PLAIN') {
                return true;
            }
        }

        return false;
    }
    /**
     * Get body HTML.
     *
     * @return string | null Null if message has no HTML message part
     */
    public function getBodyHtml()
    {
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        $html = '';
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'HTML') {
                $html .= $part->getDecodedContent();
            }
        }

        return $html;
    }

    /**
     * Get body text.
     *
     * @return string
     */
    public function getBodyText()
    {
        $html = '';

        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $part) {
            if ($part->getSubtype() == 'PLAIN') {
                $html .= $part->getDecodedContent();
            }
        }

        // If message has no parts, return content of message itself.
        return $html ?: $this->getDecodedContent();
    }

    /**
     * Get attachments (if any) linked to this e-mail.
     *
     * @return Mirasvit_Ddeboer_Imap_Message_Attachment[]
     */
    public function getAttachments()
    {
        if (null === $this->attachments) {
            foreach ($this->getParts() as $part) {
                if ($part instanceof Mirasvit_Ddeboer_Imap_Message_Attachment) {
                    $this->attachments[] = $part;
                }
                if ($part->hasChildren()) {
                    foreach ($part->getParts() as $child_part) {
                        if ($child_part instanceof Mirasvit_Ddeboer_Imap_Message_Attachment) {
                            $this->attachments[] = $child_part;
                        }
                    }
                }
            }
        }

        return $this->attachments;
    }

    /**
     * Does this message have attachments?
     *
     * @return int
     */
    public function hasAttachments()
    {
        return count($this->getAttachments()) > 0;
    }

    /**
     * Delete message.
     */
    public function delete()
    {
        // 'deleted' header changed, force to reload headers, would be better to set deleted flag to true on header
        $this->headers = null;

        if (!imap_delete($this->stream, $this->messageNumber)) {
            throw new Mirasvit_Ddeboer_Imap_MessageDeleteException($this->messageNumber);
        }
    }

    /**
     * Move message to another mailbox.
     *
     * @param Mirasvit_Ddeboer_Imap_Mailbox $mailbox
     *
     * @return Mirasvit_Ddeboer_Imap_Message
     */
    public function move(Mirasvit_Ddeboer_Imap_Mailbox $mailbox)
    {
        if (!imap_mail_move($this->stream, $this->messageNumber, $mailbox->getName())) {
            throw new Mirasvit_Ddeboer_Imap_MessageMoveException($this->messageNumber, $mailbox->getName());
        }

        return $this;
    }

    /**
     * Prevent the message from being marked as seen.
     *
     * Defaults to false, so messages that are read will be marked as seen.
     *
     * @param bool $bool
     *
     * @return Mirasvit_Ddeboer_Imap_Message
     */
    public function keepUnseen($bool = true)
    {
        $this->keepUnseen = (bool) $bool;

        return $this;
    }

    /**
     * @param string $delimiter
     * @return bool|string
     */
    public function getErrors($delimiter = ',')
    {
        if ($this->errors) {
            return implode($delimiter, $this->errors);
        } else {
            return $this->errors;
        }
    }

    /**
     * @return $this
     */
    public function checkAutoresponse()
    {
        $autoHeaders = [
            'X-Autoreply',
            'X-Autorespond',
            //'X-BeenThere', //does not work for om-ga mail
            'X-Failed-Recipients',
            'X-Auto-Response-Suppress',
            'Auto-Submitted',
        ];

        $error = '';
        foreach ($autoHeaders as $autoHeader) {
            if ($this->getHeaders()->get($autoHeader)) {
                $error = __('Auto Response');
                break;
            }
        }

        if (!$error) {
            $data = imap_fetchheader($this->stream, $this->messageNumber);
            $data = preg_split("/\r\n|\n|\r/", $data);

            foreach ($data as $row) {
                list($name) = explode(':', $row, 2);

                // skit if first char is a whitespace
                if (strpos($name, ' ') !== 0 && in_array($name, $autoHeaders)) {
                    $error = __('Auto Response');
                    break;
                }
            }
        }

        if ($error) {
            if ($this->errors) {
                $this->errors[] = $error;
            } else {
                $this->errors = [$error];
            }
        }

        return $this;
    }

    /**
     * Load message structure.
     */
    protected function loadStructure()
    {
        error_reporting(E_ERROR); //must set. otherwise some bad emails fail with notice.
        $structure = @imap_fetchstructure($this->stream, $this->messageNumber);
        $this->errors = imap_errors();
        if (!$structure) {
            $this->errors = [__('Bad email')];
        }
        if (!$this->errors) {
            $this->errors = imap_alerts();
            if (!$this->errors) {
                $this->parseStructure($structure);
            }
        }
    }
}

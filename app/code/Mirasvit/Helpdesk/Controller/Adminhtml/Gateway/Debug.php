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


namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway;

class Debug extends \Mirasvit\Helpdesk\Controller\Adminhtml\Gateway
{

    /**
     * @param string $emailNumber
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function fetch($emailNumber)
    {
        $objectManager = $this->context->getObjectManager();
        /** @var \Mirasvit\Helpdesk\Helper\Fetch $fetchHelper */
        $fetchHelper = $objectManager->get("\Mirasvit\Helpdesk\Helper\Fetch");
        $id = (int)$this->getRequest()->getParam('id');
        $gateway = $this->gatewayFactory->create()->load($id);

        $fetchHelper->connect($gateway);
        $mailbox = $fetchHelper->getMailbox();
        $message = $mailbox->getMessage($emailNumber);
        $fetchHelper->saveEmail($message);
        $fetchHelper->close();

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getResponse();

        return $response->setBody(__('done'));
    }

    /**
     * @param string $emailNumber
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function raw($emailNumber)
    {
        header("Content-Type: text/plain");
        $objectManager = $this->context->getObjectManager();
        /** @var \Mirasvit\Helpdesk\Helper\Fetch $fetchHelper */
        $fetchHelper = $objectManager->get("\Mirasvit\Helpdesk\Helper\Fetch");
        $id = (int)$this->getRequest()->getParam('id');
        $gateway = $this->gatewayFactory->create()->load($id);

        $fetchHelper->connect($gateway);
        $mailbox = $fetchHelper->getMailbox();
        /** @var \Mirasvit_Ddeboer_Imap_Mailbox|\Mirasvit\Helpdesk\Helper\GmailMailboxAdapter $mailbox */
        if ($mailbox instanceof \Mirasvit\Helpdesk\Helper\GmailMailboxAdapter) {
            $raw_full_email = $mailbox->getRawEmail($emailNumber);
        } else {
            $resource = $mailbox->connection->getResource();
            /** @var \IMAP\Connection $resource */
            $raw_full_email = imap_fetchbody($resource, (int)$emailNumber, "", FT_PEEK);
        }
        $fetchHelper->close();

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getResponse();

        return $response->setBody((string) $raw_full_email);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $output = '';
        if ($this->getRequest()->getParam('action') == 'fetch') {
            return $this->fetch($this->getRequest()->getParam('email_number'));
        }

        if ($this->getRequest()->getParam('action') == 'raw') {
            return $this->raw($this->getRequest()->getParam('email_number'));
        }

        $objectManager = $this->context->getObjectManager();
        /** @var \Mirasvit\Helpdesk\Helper\Fetch $fetchHelper */
        $fetchHelper = $objectManager->get("\Mirasvit\Helpdesk\Helper\Fetch");
        $id = (int)$this->getRequest()->getParam('id');
        $gateway = $this->gatewayFactory->create()->load($id);

        $fetchHelper->connect($gateway);
        $mailbox = $fetchHelper->getMailbox();
        /** @var \Mirasvit_Ddeboer_Imap_Mailbox|\Mirasvit\Helpdesk\Helper\GmailMailboxAdapter $mailbox */
        $emails = $mailbox->getMessages();
        //        $emails = $mailbox->getMessages('SUBJECT "8 Days of Gains"');
        $output .= "Number of emails:".count($emails)."<br>";
        $limit = 10;
        if (count($emails) < $limit) {
            $limit = count($emails);
        }
        $output .= "Show last $limit emails<br>";
        // Gmail API returns newest-first (index 1 = newest); IMAP is oldest-first (index count = newest)
        if ($mailbox instanceof \Mirasvit\Helpdesk\Helper\GmailMailboxAdapter) {
            $indices = range(1, $limit);
        } else {
            $indices = range(count($emails), count($emails) - $limit + 1);
        }
        foreach ($indices as $i) {
            /** @var \Mirasvit_Ddeboer_Imap_Message $email */
            $email = $mailbox->getMessage($i);
            /* output the email header information */
            $output .= ' - ' . $i . ': ';
            if($email->isSeen()) {
                $output .= "[<font color='green'>read</font>]";
            } else {
                if (isset($mailbox->connection) && $mailbox->connection) {
                    $resource = $mailbox->connection->getResource();
                    /** @var \IMAP\Connection $resource */
                    imap_clearflag_full($resource, (string)$i, '\\Seen');
                }
                $output .= "[<font color='red'>unread</font>]";
            }
            $output .= " ".$email->getSubject()." | ".$email->getFrom()." | ";
            $output .= "<a href='".$this->getUrl("*/*/*", ["id"=>$id, "action"=>"fetch", "email_number" => $email->getNumber()])."'>fetch again</a> ";
            $output .= "<a href='".$this->getUrl("*/*/*", ["id"=>$id, "action"=>"raw", "email_number" => $email->getNumber()])."'>raw</a>";
            $output .= "<br>";
        }
        $fetchHelper->close();

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $this->getResponse();

        return $response->setBody($output);
    }


}

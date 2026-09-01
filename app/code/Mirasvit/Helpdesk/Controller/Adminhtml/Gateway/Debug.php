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


namespace Mirasvit\Helpdesk\Controller\Adminhtml\Gateway;

class Debug extends \Mirasvit\Helpdesk\Controller\Adminhtml\Gateway
{

    /**
     * @param string $emailNumber
     * @return mixed
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
        return $this->getResponse()->setBody(__('done'));
    }

    /**
     * @param string $emailNumber
     * @return mixed
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
        $raw_full_email = imap_fetchbody($mailbox->connection->getResource(), $emailNumber, "", FT_PEEK);
        $fetchHelper->close();
        return $this->getResponse()->setBody($raw_full_email);
    }

    /**
     *
     */
    public function execute()
    {
        $output = '';
        if ($this->getRequest()->getParam('action') == 'fetch') {
            $this->fetch($this->getRequest()->getParam('email_number'));
            return;
        }

        if ($this->getRequest()->getParam('action') == 'raw') {
            $this->raw($this->getRequest()->getParam('email_number'));
            return;
        }

        $objectManager = $this->context->getObjectManager();
        /** @var \Mirasvit\Helpdesk\Helper\Fetch $fetchHelper */
        $fetchHelper = $objectManager->get("\Mirasvit\Helpdesk\Helper\Fetch");
        $id = (int)$this->getRequest()->getParam('id');
        $gateway = $this->gatewayFactory->create()->load($id);

        $fetchHelper->connect($gateway);
        $mailbox = $fetchHelper->getMailbox();
        $emails = $mailbox->getMessages();
        //        $emails = $mailbox->getMessages('SUBJECT "8 Days of Gains"');
        $output .= "Number of emails:".count($emails)."<br>";
        $limit = 10;
        if (count($emails) < $limit) {
            $limit = count($emails);
        }
        $output .= "Show last $limit emails<br>";
        for($i = count($emails); $i > count($emails) - $limit ; $i--) {
            /** @var \Mirasvit_Ddeboer_Imap_Message $email */
            $email = $mailbox->getMessage($i);
            /* output the email header information */
            $output .= ' - ' . $i . ': ';
            if($email->isSeen()) {
                $output .= "[<font color='green'>read</font>]";
            } else {
                imap_clearflag_full($mailbox->connection->getResource(), (string)$i, '\\Seen');
                $output .= "[<font color='red'>unread</font>]";
            }
            $output .= " ".$email->getSubject()." | ".$email->getFrom()." | ";
            $output .= "<a href='".$this->getUrl("*/*/*", ["id"=>$id, "action"=>"fetch", "email_number" => $email->getNumber()])."'>fetch again</a> ";
            $output .= "<a href='".$this->getUrl("*/*/*", ["id"=>$id, "action"=>"raw", "email_number" => $email->getNumber()])."'>raw</a>";
            $output .= "<br>";
        }
        $fetchHelper->close();

        return $this->getResponse()->setBody($output);
    }


}

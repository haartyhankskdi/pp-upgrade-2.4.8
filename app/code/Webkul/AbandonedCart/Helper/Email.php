<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\AbandonedCart\Helper;

use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

/**
 * Webkul AbandonedCart Helper Email.
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param Magento\Framework\App\Helper\Context $context
     * @param \Webkul\AbandonedCart\Model\MailsLog $logMails,
     * @param \Webkul\AbandonedCart\Logger\Logger $logger
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Webkul\AbandonedCart\Model\MailsLog $logMails,
        \Webkul\AbandonedCart\Logger\Logger $logger,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_logger = $logger;
        $this->_logMails = $logMails;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param string $adminNameInMail
     * @param string $adminEmailAddress
     * @param string $mailBody
     * @param string $recieverEmail
     * @param string $template
     * @param string $name
     * send followup mails
     **/
    public function sendFollowMail($adminNameInMail, $adminEmailAddress, $mailBody, $recieverEmail, $template, $name)
    {
        $senderInfo = ['name' => $adminNameInMail, 'email' => $adminEmailAddress];
        $receiverInfo = ['name' => $name, 'email' => $recieverEmail];

        $emailTempVariables = [
            'store' => $this->_storeManager->getStore(),
            'message' => $mailBody,
            'customername' => $name,
            'adminname' => $adminNameInMail
        ];
        $this->generateTemplate($emailTempVariables, $senderInfo, $receiverInfo, $template);

        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->_inlineTranslation->resume();
    }

    /**
     * generate email template
     * @param string $emailTemplateVariables
     * @param array $senderInfo
     * @param array $receiverInfo
     * @param string $emailTempId
     **/
    public function generateTemplate(
        $emailTemplateVariables,
        $senderInfo,
        $receiverInfo,
        $emailTempId
    ) {
        $template =  $this->_transportBuilder->setTemplateIdentifier($emailTempId)->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->_storeManager->getStore()->getId(),
            ]
        )->setTemplateVars($emailTemplateVariables)->setFrom($senderInfo)
        ->addTo($receiverInfo['email'], $receiverInfo['name']);
        return $this;
    }

    /**
     * logs the sent mail
     * @param array $mail
     */
    public function logSentMail($mail)
    {
        try {
            $this->_logMails->setData($mail)->save();
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}

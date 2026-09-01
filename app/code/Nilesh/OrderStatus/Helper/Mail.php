<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\OrderStatus\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;

class Mail extends AbstractHelper
{

    const XML_SENDER_EMAIL_APPROVE          = 'sales_email/custom_approve_status/identity';
    const XML_SENDER_EMAIL_APPROVE_CC       = 'sales_email/custom_approve_status/copy_to';
    const XML_SENDER_EMAIL_APPROVE_ENABLE   = 'sales_email/custom_approve_status/enable';
    const XML_SENDER_EMAIL_DISAPPROVE       = 'sales_email/custom_disapprove_status/identity';
    const XML_SENDER_EMAIL_DISAPPROVE_CC    = 'sales_email/custom_disapprove_status/copy_to';
    const XML_SENDER_EMAIL_DISAPPROVE_ENABLE   = 'sales_email/custom_disapprove_status/enable';

    protected $storeManager;
    protected $transportBuilder;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder@param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SenderResolverInterface $senderResolver
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->senderResolver = $senderResolver;
        parent::__construct($context);
    }

    /**
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $to email and name of the receiver
     * @param array $templateParams
     * @param int|null $storeId
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function sendEmailTemplate(
        $template,
        $fromArray = [],
        $to,
        $add_cc = [],
        $templateParams = [],
        $storeId = null
    ) {
        if (!isset($to) || empty($to)) {
            throw new LocalizedException(
                __('We could not send the email because the receiver data is invalid.')
            );
        }
        $storeId = $storeId ? $storeId : $this->storeManager->getStore()->getId();
        $name = isset($fromArray['name']) ? $fromArray['name'] : '';

        $from = [
            'name' => $name,
            'email' => $fromArray['email'],
        ];

        /** @var \Magento\Framework\Mail\TransportInterface $transport */
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId)
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            $templateParams
        )->setFrom(
            $from
        )->addTo(
            $to
        )->addCc(
            $add_cc
        )->getTransport();
        $transport->sendMessage();
    }

    /**
     * Send the ApproveTemplate Email
     */
    public function sendApproveTemplateEmail(
        $to,
        $templateParams = []
    ) {
        $from = $this->getSenderEmail(self::XML_SENDER_EMAIL_APPROVE);
        $add_cc = $this->getCcArray(self::XML_SENDER_EMAIL_APPROVE_CC);
        $isAllow = $this->isAllowed(self::XML_SENDER_EMAIL_APPROVE_ENABLE);

        // check if custName is not there in templateParams array
        if(!isset($templateParams['custName'])){
            $templateParams['custName'] = "Customer";
        }

        if($isAllow){
            // throw new LocalizedException(
            //     __('Disapprove - '.$isAllow)
            // );
            $this->sendEmailTemplate(
                'sales_email/custom_approve_status/approve_template',
                $from,
                $to,
                $add_cc,
                $templateParams
            );
        }
    }
    
    /**
     * Send the DisapproveTemplate Email
     */
    public function sendDisapproveTemplateEmail(
        $to,
        $templateParams = []
    ) {
        $from = $this->getSenderEmail(self::XML_SENDER_EMAIL_DISAPPROVE);
        $isAllow = $this->isAllowed(self::XML_SENDER_EMAIL_DISAPPROVE_ENABLE);
        $add_cc = $this->getCcArray(self::XML_SENDER_EMAIL_DISAPPROVE_CC);

        // check if custName is not there in templateParams array
        if(!isset($templateParams['custName'])){
            $templateParams['custName'] = "Customer";
        }
        
        if($isAllow){
            // throw new LocalizedException(
            //     __('Disapprove'.$isAllow)
            // );
            $this->sendEmailTemplate(
                'sales_email/custom_disapprove_status/disapprove_template',
                $from,
                $to,
                $add_cc,
                $templateParams
            );
        }
    }

    public function getSenderEmail( $xmlPath = "sales_email/order/identity" )
    {
        return $this->senderResolver->resolve($this->scopeConfig->getValue(
            $xmlPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));
    }

    public function getCcArray(String $xmlPath = null)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if($xmlPath != null){
            $emailComma = $this->scopeConfig->getValue($xmlPath, ScopeInterface::SCOPE_STORE, $storeId);
            if (!empty($emailComma)) {
                return (array) \explode(",", $emailComma);
            }
        }
        return [];
    }
    
    public function isAllowed(String $xmlPath = null)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if($xmlPath != null){
            return $this->scopeConfig->getValue($xmlPath, ScopeInterface::SCOPE_STORE, $storeId);
        }
        return 0;
    }
}


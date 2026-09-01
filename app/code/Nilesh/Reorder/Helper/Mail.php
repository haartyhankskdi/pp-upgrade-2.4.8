<?php
declare(strict_types=1);

namespace Nilesh\Reorder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;

class Mail extends AbstractHelper
{

    const XML_SENDER_EMAIL_REORDER          = 'sales_email/reoder_email/identifier';
    const XML_SENDER_EMAIL_REORDER_ENABLE   = 'sales_email/reoder_email/enable';

    protected $transportBuilder;
    protected $storeManager;
    protected $senderResolver;

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
        $this->storeManager     = $storeManager;
        $this->senderResolver   = $senderResolver;
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
        $templateParams = [],
        $storeId = null
    ) {
        if (!isset($to) || empty($to)) {
            throw new LocalizedException(
                __('We could not send the email because the receiver data is invalid.')
            );
            return false;
            exit("Hello");
        }
        $storeId = $storeId ? $storeId : $this->storeManager->getStore()->getId();
        $name = isset($fromArray['name']) ? $fromArray['name'] : '';

        $from = [
            'name' => $name,
            'email' => $fromArray['email'],
        ];

        // echo $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId);
        // exit();

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
        )->getTransport();

        if($transport->sendMessage()){
            return true;
        }else{
            return false;
        }
        // exit();
    }

    /**
     * Send the Reorder Template Email
     */
    public function sendReorderTemplateEmail( $to, $tempVar = [] ) {
        $from = $this->getSenderEmail(self::XML_SENDER_EMAIL_REORDER);
        $isAllow = $this->isAllowed(self::XML_SENDER_EMAIL_REORDER_ENABLE);
        if($isAllow){
            // throw new LocalizedException(
            //     __('cccc - ' . $isAllow)
            // );
            // print_r($from);
            // exit();
           return $this->sendEmailTemplate(
                'sales_email/reoder_email/template',
                $from,
                $to,
                $tempVar
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

    public function isAllowed(String $xmlPath = null)
    {
        $storeId = $this->storeManager->getStore()->getId();
        if($xmlPath != null){
            return $this->scopeConfig->getValue($xmlPath, ScopeInterface::SCOPE_STORE, $storeId);
        }
        return 0;
    }
}


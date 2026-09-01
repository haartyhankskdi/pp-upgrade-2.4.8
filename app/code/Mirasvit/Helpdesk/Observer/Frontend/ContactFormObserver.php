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



namespace Mirasvit\Helpdesk\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ContactFormObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ContactFormObserver constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->scopeConfig->getValue('msp_securitysuite_recaptcha/frontend/enabled')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \MSP\ReCaptcha\Observer\ReCaptchaObserver $contactFormObserver */
            $contactFormObserver = $objectManager->get('Mirasvit\Helpdesk\Observer\Frontend\HelpdeskFormsObserver');
            $contactFormObserver->execute($observer);
        }
    }
}
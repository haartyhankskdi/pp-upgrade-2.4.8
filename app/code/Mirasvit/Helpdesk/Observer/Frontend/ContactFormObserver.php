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



namespace Mirasvit\Helpdesk\Observer\Frontend;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Helpdesk\Model\Config;

class ContactFormObserver implements ObserverInterface
{
    private   $scopeConfig;

    protected $redirect;

    protected $storeManager;

    protected $config;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RedirectInterface $redirect,
        StoreManagerInterface $storeManager,
        Config $config
    )
    {
        $this->scopeConfig    = $scopeConfig;
        $this->redirect       = $redirect;
        $this->storeManager   = $storeManager;
        $this->config         = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer): void
    {
        if ($this->config->getExtendedSettingsShowCaptcha($this->storeManager->getStore())) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            if ($this->scopeConfig->getValue('msp_securitysuite_recaptcha/frontend/enabled')) {
                /** @var \MSP\ReCaptcha\Observer\ReCaptchaObserver $contactFormObserver */
                $contactFormObserver = $objectManager->get('Mirasvit\Helpdesk\Observer\Frontend\HelpdeskFormsObserver');
                $contactFormObserver->execute($observer);
            } elseif ($this->scopeConfig->getValue("recaptcha_frontend/type_for/contact")) {
                /** @var \Magento\Framework\App\Action\Action $controller */
                $controller = $observer->getControllerAction();
                $request = $controller->getRequest();
                $response = $controller->getResponse();
                $redirectOnFailureUrl = $this->redirect->getRedirectUrl();
                $requestHandler = $objectManager->get('Magento\ReCaptchaUi\Model\RequestHandlerInterface');
                $requestHandler->execute('contact', $request, $response, $redirectOnFailureUrl);
            }
        }
    }
}

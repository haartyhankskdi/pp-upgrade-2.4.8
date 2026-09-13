<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data;

class CredentialValidator extends Field
{
    /**
     * @var string
     */
    protected $_template = 'AutifyDigital_LloydscardnetPayment::system/config/credential_validator.phtml';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * Remove scope label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->setData('scope', null);
        $element->setData('can_use_website_value', null);
        $element->setData('can_use_default_value', null);

        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for credential validation
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('autify_lloydscardnetpayment/system/healthcheck');
    }

    /**
     * Generate validation button html
     *
     * @return string
     */
    public function getButtonHtml(): string
    {
        /** @var \Magento\Backend\Block\Widget\Button $lbopButton */
        $lbopButton = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        );
        $lbopButton->setData([
            'id' => 'lloydscardnet_credentials',
            'label' => __('Validate Credentials'),
            'class' => 'action-default',
            'onclick' => 'javascript:validateCredentials(false); return false;'
        ]);

        return $lbopButton->toHtml();
    }

    /**
     * Retrieve Mode
     *
     * @return String
     */
    public function getMode()
    {
        return $this->helper->getMode();
    }

    /**
     * Resolve the store ID for the configuration scope currently being edited.
     *
     * Reads the admin System Config "store"/"website" request params so the
     * credential validation runs against the scope the admin is editing rather
     * than the default scope. Returns 0 for the default scope.
     *
     * @return int
     */
    public function getConfigScopeStoreId(): int
    {
        $storeParam = $this->getRequest()->getParam('store');
        if ($storeParam) {
            return (int) $this->storeManager->getStore($storeParam)->getId();
        }

        $websiteParam = $this->getRequest()->getParam('website');
        if ($websiteParam) {
            $defaultGroupId = $this->storeManager->getWebsite($websiteParam)->getDefaultGroupId();
            if ($defaultGroupId) {
                $defaultStoreId = $this->storeManager->getGroup((int) $defaultGroupId)->getDefaultStoreId();
                if ($defaultStoreId) {
                    return (int) $defaultStoreId;
                }
            }
        }

        return 0;
    }
}

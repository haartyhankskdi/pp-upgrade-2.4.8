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
 
namespace AutifyDigital\LloydscardnetPayment\Block\Googlepay;
 
use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use Magento\Store\Model\Store;

class Minicart extends Template implements ShortcutInterface
{

    /**
     * @var string
     */
    protected $_template = 'AutifyDigital_LloydscardnetPayment::googlepay/minicart-button.phtml';
     
    /**
     * @var Session
     */
    private $checkoutSession;
 
    /**
     * @var HelperData
     */
    protected $helperData;
 
    /**
     * Minicart constructor.
     *
     * @param Template\Context $context
     * @param Session $checkoutSession
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->helperData = $helperData;
    }
 
    /**
     * Get Alias
     *
     * @return string
     */
    public function getAlias(): string
    {
        return 'lloydscardnet.googlepay.mini-cart';
    }
 
    /**
     * Get Base Grand Total
     *
     * @return float|null
     */
    public function getBaseGrandTotal(): ?float
    {
        return (float)$this->checkoutSession->getQuote()->getBaseGrandTotal();
    }
 
    /**
     * Get Store Name
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->_storeManager->getStore()->getName();
    }
 
    /**
     * Get Store Country
     *
     * @return string
     */
    public function getStoreCountry(): string
    {
        return $this->_scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE);
    }
 
    /**
     * Get Store Currency
     *
     * @return string
     */
    public function getStoreCurrency(): string
    {
        /** @var Store $store */
        $store = $this->_storeManager->getStore();
        return $store->getCurrentCurrency()->getCode();
    }
 
    /**
     * Get Extra Classname
     *
     * @return string
     */
    public function getExtraClassname(): string
    {
        return 'minicart';
    }
     
    /**
     * Returns the helper object.
     *
     * @return HelperData
     */
    public function getHelper()
    {
        return $this->helperData;
    }
}

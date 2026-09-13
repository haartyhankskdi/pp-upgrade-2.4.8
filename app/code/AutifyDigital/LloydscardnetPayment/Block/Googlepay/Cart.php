<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

namespace AutifyDigital\LloydscardnetPayment\Block\Googlepay;

use Magento\Framework\View\Element\Template;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Locale\Resolver;
use Magento\Directory\Model\CountryFactory;
use Magento\Store\Model\StoreManagerInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use Magento\Store\Model\Store;

class Cart extends Template
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Resolver
     */
    protected $localeResolver;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Template\Context $context
     * @param CheckoutSession $checkoutSession
     * @param Resolver $localeResolver
     * @param CountryFactory $countryFactory
     * @param StoreManagerInterface $storeManager
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        Resolver $localeResolver,
        CountryFactory $countryFactory,
        StoreManagerInterface $storeManager,
        HelperData $helperData,
        array $data = []
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->countryFactory = $countryFactory;
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        parent::__construct($context, $data);
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
    
    /**
     * Get cart grand total
     *
     * @return string
     */
    public function getCartGrandTotal()
    {
        return number_format((float)$this->checkoutSession->getQuote()->getGrandTotal(), 2, '.', '');
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return $store->getCurrentCurrency()->getCode();
    }

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return 'GB';
    }
}

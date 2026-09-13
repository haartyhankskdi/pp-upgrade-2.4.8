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

namespace AutifyDigital\LloydscardnetPayment\Block\Form;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\View\Element\Template\Context;
use AutifyDigital\LloydscardnetPayment\Helper\Data;

/**
 * Lcnetpaymentjs Block Class
 */
class Lcnetpaymentjs extends \Magento\Payment\Block\Form
{
    /**
     * Lcnetpaymentjs template
     *
     * @var string
     */
    protected $_template = 'AutifyDigital_LloydscardnetPayment::form/lcnetpaymentjs.phtml'; // phpcs:ignore

    /**
     * Payment config model
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig; // phpcs:ignore
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Quote
     */
    protected $backendQuoteSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig Payment configuration
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helper Custom helper
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession Backend quote session
     * @param array $data Additional data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper,
        Quote $backendQuoteSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_paymentConfig = $paymentConfig;
        $this->helper = $helper;
        $this->backendQuoteSession = $backendQuoteSession;
    }

    /**
     * Get Current Customer Id
     */
    public function getCurrentCustomerId()
    {
        $quote = $this->backendQuoteSession->getQuote();
        return $quote->getCustomerId();
    }

    /**
     * Get all saved cards for the logged in customer
     *
     * @return array
     */
    public function getSavedCards()
    {
        if ($this->getCurrentCustomerId()) {
            $customerId = $this->getCurrentCustomerId();
            $cardArr = $this->helper->getPaymentTokensAdminByCustomerId($customerId);
            if ($cardArr) {
                return $cardArr;
            }
        }
        return [];
    }
}

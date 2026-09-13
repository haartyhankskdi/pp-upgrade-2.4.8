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

namespace AutifyDigital\LloydscardnetPayment\Observer;

use Magento\Framework\Session\SessionManagerInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as HelperData;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Predispatch Ovserber Class
 */
class ActionPredispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var sessionManagerInterface
     */
    protected $sessionManagerInterface;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    private $helper;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var SessionManagerInterface
     */
    protected $_coreSession; // phpcs:ignore

    /**
     * Construct
     *
     * @param SessionManagerInterface $sessionManagerInterface
     * @param HelperData $helperData
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        SessionManagerInterface $sessionManagerInterface,
        HelperData $helperData,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->sessionManagerInterface = $sessionManagerInterface;
        $this->helper = $helperData;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        // Restore Quote if payment failed
        $lloydsCookie = $this->helper->getLloydsCookie();
        if ($lloydsCookie) {
            $this->helper->restoreQuote();
            $this->helper->deleteLloydsCookie();
        }

        $request = $observer->getEvent()->getData('request');
        $actionFullName = strtolower($request->getFullActionName());
        $redirectArray = [
            'lcnetpayment_index_redirectresponse',
            'lcnetpayment_index_redirectpostdata',
            'lcnetpayment_index_directresponse',
            'lcnetpayment_index_directpostdata',
            'lcnetpayment_index_confirmresponse',
            'lloyds_paymentjs_termresponse',
            'lloyds_paymentjs_methodnotificationurl',
            'lloyds_paymentjs_processjsdata',
            'lloyds_paymentjs_transactionnotificationurl',
            'lloyds_paymentjs_threedsframe',
            'lloyds_paymentjs_cards',
            'lloyds_paymentjs_delete'
        ];

        if (in_array($actionFullName, $redirectArray)) {
            $this->_coreSession = $this->sessionManagerInterface;
            if (!$this->_coreSession->getSessionId() ||
                $request->getParam('SID') !== $this->_coreSession->getSessionId()
            ) {
                $this->_coreSession->setSessionId($request->getParam('SID'));
            }
        }
    }
}

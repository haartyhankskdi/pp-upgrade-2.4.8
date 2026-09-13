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

namespace AutifyDigital\LloydscardnetPayment\Block;

use Magento\Framework\View\Element\Template;

class MotoResponse extends Template
{
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get payment status message
     *
     * @return \Magento\Framework\Phrase
     */
    public function getPaymentStatusMessage()
    {
        $status = $this->getData('status');
        $approvalCode = $this->getData('approval_code');
        
        if ($status === 'APPROVED' && $this->helper->startsWith($approvalCode, 'Y:')) {
            return __('Payment has been approved successfully.');
        } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
            return __('Payment was cancelled.');
        } else {
            return __('Payment was declined or failed.');
        }
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData('order_id');
    }
}

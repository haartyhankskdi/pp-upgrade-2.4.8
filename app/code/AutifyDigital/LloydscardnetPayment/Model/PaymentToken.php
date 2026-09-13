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

namespace AutifyDigital\LloydscardnetPayment\Model;

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenInterface;
use Magento\Framework\Model\AbstractModel;

class PaymentToken extends AbstractModel implements PaymentTokenInterface
{
    /**
     * Initialize resource model
     */
    public function _construct()
    {
        $this->_init(\AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken::class);
    }

    /**
     * Get payment token ID
     *
     * @return int|null
     */
    public function getPaymenttokenId()
    {
        return $this->getData(self::PAYMENTTOKEN_ID);
    }

    /**
     * Set payment token ID
     *
     * @param int $paymenttokenId
     * @return $this
     */
    public function setPaymenttokenId($paymenttokenId)
    {
        return $this->setData(self::PAYMENTTOKEN_ID, $paymenttokenId);
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get payment token
     *
     * @return string|null
     */
    public function getPaymentToken()
    {
        return $this->getData(self::PAYMENT_TOKEN);
    }

    /**
     * Set payment token
     *
     * @param string $paymentToken
     * @return $this
     */
    public function setPaymentToken($paymentToken)
    {
        return $this->setData(self::PAYMENT_TOKEN, $paymentToken);
    }

    /**
     * Get masked
     *
     * @return string|null
     */
    public function getMasked()
    {
        return $this->getData(self::MASKED);
    }

    /**
     * Set masked
     *
     * @param string $masked
     * @return $this
     */
    public function setMasked($masked)
    {
        return $this->setData(self::MASKED, $masked);
    }

    /**
     * Get brand
     *
     * @return string|null
     */
    public function getBrand()
    {
        return $this->getData(self::BRAND);
    }

    /**
     * Set brand
     *
     * @param string $brand
     * @return $this
     */
    public function setBrand($brand)
    {
        return $this->setData(self::BRAND, $brand);
    }

    /**
     * Get exp month
     *
     * @return string|null
     */
    public function getExpMonth()
    {
        return $this->getData(self::EXP_MONTH);
    }

    /**
     * Set exp month
     *
     * @param string $expMonth
     * @return $this
     */
    public function setExpMonth($expMonth)
    {
        return $this->setData(self::EXP_MONTH, $expMonth);
    }

    /**
     * Get exp year
     *
     * @return string|null
     */
    public function getExpYear()
    {
        return $this->getData(self::EXP_YEAR);
    }

    /**
     * Set exp year
     *
     * @param string $expYear
     * @return $this
     */
    public function setExpYear($expYear)
    {
        return $this->setData(self::EXP_YEAR, $expYear);
    }

    /**
     * Get last4
     *
     * @return string|null
     */
    public function getLast4()
    {
        return $this->getData(self::LAST4);
    }

    /**
     * Set last4
     *
     * @param string $last4
     * @return $this
     */
    public function setLast4($last4)
    {
        return $this->setData(self::LAST4, $last4);
    }
}

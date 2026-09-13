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

namespace AutifyDigital\LloydscardnetPayment\Api\Data;

/**
 * Interface PaymentTokenInterface
 */
interface PaymentTokenInterface
{
    public const PAYMENTTOKEN_ID = 'paymenttoken_id';
    public const PAYMENT_TOKEN = 'payment_token';
    public const CUSTOMER_ID = 'customer_id';
    public const MASKED = 'masked';
    public const BRAND = 'brand';
    public const EXP_MONTH = 'exp_month';
    public const EXP_YEAR = 'exp_year';
    public const LAST4 = 'last4';

    /**
     * Get paymenttoken_id
     *
     * @return int|null
     */
    public function getPaymenttokenId();

    /**
     * Set paymenttoken_id
     *
     * @param int $paymenttokenId
     */
    public function setPaymenttokenId($paymenttokenId);

    /**
     * Get customer_id
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param int $customerId
     */
    public function setCustomerId($customerId);

    /**
     * Get payment_token
     *
     * @return string|null
     */
    public function getPaymentToken();

    /**
     * Set payment_token
     *
     * @param string $paymentToken
     */
    public function setPaymentToken($paymentToken);

    /**
     * Get masked
     *
     * @return string|null
     */
    public function getMasked();

    /**
     * Set masked
     *
     * @param string $masked
     */
    public function setMasked($masked);

    /**
     * Get brand
     *
     * @return string|null
     */
    public function getBrand();

    /**
     * Set brand
     *
     * @param string $brand
     */
    public function setBrand($brand);

    /**
     * Get expiration month
     *
     * @return string|null
     */
    public function getExpMonth();

    /**
     * Set expiration month
     *
     * @param string $expMonth
     */
    public function setExpMonth($expMonth);

    /**
     * Get expiration year
     *
     * @return string|null
     */
    public function getExpYear();

    /**
     * Set expiration year
     *
     * @param string $expYear
     */
    public function setExpYear($expYear);

    /**
     * Get last 4 digits of the card number
     *
     * @return string|null
     */
    public function getLast4();

    /**
     * Set last 4 digits of the card number
     *
     * @param string $last4
     */
    public function setLast4($last4);
}

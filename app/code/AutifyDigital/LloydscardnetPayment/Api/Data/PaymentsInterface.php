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

interface PaymentsInterface
{
    public const AMOUNT = 'amount';
    public const PAYMENTS_ID = 'payments_id';
    public const ORDER_ID = 'order_id';
    public const REMOTE_REFERENCE = 'remote_reference';
    public const STATUS = 'status';
    public const REMOTE_MESSAGE = 'remote_message';
    public const REDIRECT_EMAIL_SENT = 'redirect_email_sent';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const CARDNET_ORDER_ID = 'cardnet_order_id';
    public const REMOTE_STATUS_OR_CODE = 'remote_status_or_code';
    public const BRAND = 'brand';
    public const LAST4 = 'last4';
    public const IPG_TRANSACTION_ID = 'ipg_transaction_id';
    public const IFRAME_RECEIVED = 'iframe_received';
    public const THREEDS_RECEIVED = 'threeds_received';

    /**
     * Get payments_id
     *
     * @return string|null
     */
    public function getPaymentsId();

    /**
     * Set payments_id
     *
     * @param string $paymentsId
     */
    public function setPaymentsId($paymentsId);

    /**
     * Get amount
     *
     * @return string|null
     */
    public function getAmount();

    /**
     * Set amount
     *
     * @param string $amount
     */
    public function setAmount($amount);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     */
    public function setStatus($status);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     */
    public function setOrderId($orderId);

    /**
     * Get order_increment_id
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set order_increment_id
     *
     * @param string $orderIncrementId
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get remote_reference
     *
     * @return string|null
     */
    public function getRemoteReference();

    /**
     * Set remote_reference
     *
     * @param string $remoteReference
     */
    public function setRemoteReference($remoteReference);

    /**
     * Get remote_message
     *
     * @return string|null
     */
    public function getRemoteMessage();

    /**
     * Set remote_message
     *
     * @param string $remoteMessage
     */
    public function setRemoteMessage($remoteMessage);

    /**
     * Get remote_status_or_code
     *
     * @return string|null
     */
    public function getRemoteStatusOrCode();

    /**
     * Set remote_status_or_code
     *
     * @param string $remoteStatusOrCode
     */
    public function setRemoteStatusOrCode($remoteStatusOrCode);

    /**
     * Get cardnet_order_id
     *
     * @return string|null
     */
    public function getCardnetOrderId();

    /**
     * Set cardnet_order_id
     *
     * @param string $cardnetOrderId
     */
    public function setCardnetOrderId($cardnetOrderId);

    /**
     * Get redirect_email_sent
     *
     * @return string|null
     */
    public function getRedirectEmailSent();

    /**
     * Set redirect_email_sent
     *
     * @param string $redirectEmailSent
     */
    public function setRedirectEmailSent($redirectEmailSent);

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
     * Get ipgTransactionId
     *
     * @return string|null
     */
    public function getIpgTransactionId();

    /**
     * Set ipgTransactionId
     *
     * @param string $ipgTransactionId
     */
    public function setIpgTransactionId($ipgTransactionId);

    /**
     * Get iframe_received
     *
     * @return int|null
     */
    public function getIframeReceived();

    /**
     * Set iframe_received
     *
     * @param int $iframeReceived
     */
    public function setIframeReceived($iframeReceived);

    /**
     * Get threeds_received
     *
     * @return int|null
     */
    public function getThreedsReceived();

    /**
     * Set threeds_received
     *
     * @param int $threedsReceived
     */
    public function setThreedsReceived($threedsReceived);
}

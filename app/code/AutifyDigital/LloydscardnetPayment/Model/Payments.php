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

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface;
use Magento\Framework\Model\AbstractModel;

class Payments extends AbstractModel implements PaymentsInterface
{
   /**
    * Construct
    */
    public function _construct()
    {
        $this->_init(\AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments::class);
    }

    /**
     * Get Payments Id
     */
    public function getPaymentsId()
    {
        return $this->getData(self::PAYMENTS_ID);
    }

    /**
     * Set Payments Id
     *
     * @param string $paymentsId
     */
    public function setPaymentsId($paymentsId)
    {
        return $this->setData(self::PAYMENTS_ID, $paymentsId);
    }

    /**
     * Get Amount
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * Set Amount
     *
     * @param string $amount
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

     /**
      * Get Status
      */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Order Id
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set Order Id
     *
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get Order Increment Id
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * Set Order Increment Id
     *
     * @param string $orderIncrementId
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * Get Remote Reference
     */
    public function getRemoteReference()
    {
        return $this->getData(self::REMOTE_REFERENCE);
    }

    /**
     * Set Remote Reference
     *
     * @param string $remoteReference
     */
    public function setRemoteReference($remoteReference)
    {
        return $this->setData(self::REMOTE_REFERENCE, $remoteReference);
    }

    /**
     * Get Remote Message
     */
    public function getRemoteMessage()
    {
        return $this->getData(self::REMOTE_MESSAGE);
    }

    /**
     * Set Remote Message
     *
     * @param string $remoteMessage
     */
    public function setRemoteMessage($remoteMessage)
    {
        return $this->setData(self::REMOTE_MESSAGE, $remoteMessage);
    }

    /**
     * Get Remote Status Or Code
     */
    public function getRemoteStatusOrCode()
    {
        return $this->getData(self::REMOTE_STATUS_OR_CODE);
    }

    /**
     * Set Remote Status Or Code
     *
     * @param string $remoteStatusOrCode
     */
    public function setRemoteStatusOrCode($remoteStatusOrCode)
    {
        return $this->setData(self::REMOTE_STATUS_OR_CODE, $remoteStatusOrCode);
    }

   /**
    * Get Cardnet Order Id
    */
    public function getCardnetOrderId()
    {
        return $this->getData(self::CARDNET_ORDER_ID);
    }

    /**
     * Set Cardnet Order Id
     *
     * @param string $cardnetOrderId
     */
    public function setCardnetOrderId($cardnetOrderId)
    {
        return $this->setData(self::CARDNET_ORDER_ID, $cardnetOrderId);
    }

    /**
     * Get Redirect Email Sent
     */
    public function getRedirectEmailSent()
    {
        return $this->getData(self::REDIRECT_EMAIL_SENT);
    }

    /**
     * Set Redirect Email Sent
     *
     * @param string $redirectEmailSent
     */
    public function setRedirectEmailSent($redirectEmailSent)
    {
        return $this->setData(self::REDIRECT_EMAIL_SENT, $redirectEmailSent);
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
     * Get Ipg Transaction Id
     */
    public function getIpgTransactionId()
    {
        return $this->getData(self::IPG_TRANSACTION_ID);
    }

    /**
     * Set Ipg Transaction Id
     *
     * @param string $ipgTransactionId
     */
    public function setIpgTransactionId($ipgTransactionId)
    {
        return $this->setData(self::IPG_TRANSACTION_ID, $ipgTransactionId);
    }

    /**
     * Get Iframe Received
     *
     * @return int|null
     */
    public function getIframeReceived()
    {
        return $this->getData(self::IFRAME_RECEIVED);
    }

    /**
     * Set Iframe Received
     *
     * @param int $iframeReceived
     * @return $this
     */
    public function setIframeReceived($iframeReceived)
    {
        return $this->setData(self::IFRAME_RECEIVED, $iframeReceived);
    }

    /**
     * Get 3DS Received
     *
     * @return int|null
     */
    public function getThreedsReceived()
    {
        return $this->getData(self::THREEDS_RECEIVED);
    }

    /**
     * Set 3DS Received
     *
     * @param int $threedsReceived
     * @return $this
     */
    public function setThreedsReceived($threedsReceived)
    {
        return $this->setData(self::THREEDS_RECEIVED, $threedsReceived);
    }
}

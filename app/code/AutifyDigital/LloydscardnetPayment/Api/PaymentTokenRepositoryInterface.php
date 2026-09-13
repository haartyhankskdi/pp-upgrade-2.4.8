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

namespace AutifyDigital\LloydscardnetPayment\Api;

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface PaymentTokenRepositoryInterface
{
    /**
     * Save PaymentToken
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(PaymentTokenInterface $paymentToken): PaymentTokenInterface;

    /**
     * Retrieve PaymentToken
     *
     * @param mixed $paymenttokenId
     * @return PaymentTokenInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($paymenttokenId): PaymentTokenInterface;

    /**
     * Retrieve PaymentToken matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return PaymentTokenSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): PaymentTokenSearchResultsInterface;

    /**
     * Delete PaymentToken
     *
     * @param PaymentTokenInterface $paymentToken
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(PaymentTokenInterface $paymentToken): bool;

    /**
     * Delete PaymentToken by ID
     *
     * @param mixed $paymenttokenId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($paymenttokenId): bool;
}

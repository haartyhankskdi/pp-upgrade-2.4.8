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

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface PaymentsRepositoryInterface
{
    /**
     * Save Payments
     *
     * @param PaymentsInterface $payments
     * @return PaymentsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(PaymentsInterface $payments): PaymentsInterface;

    /**
     * Retrieve Payments
     *
     * @param mixed $paymentsId
     * @return PaymentsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($paymentsId): PaymentsInterface;

    /**
     * Retrieve Payments matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return PaymentsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): PaymentsSearchResultsInterface;

    /**
     * Delete Payments
     *
     * @param PaymentsInterface $payments
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(PaymentsInterface $payments): bool;

    /**
     * Delete Payments by ID
     *
     * @param mixed $paymentsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($paymentsId): bool;
}

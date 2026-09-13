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

interface PaymentsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Payments list.
     *
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface[]
     */
    public function getItems();

    /**
     * Set amount list.
     *
     * @param \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

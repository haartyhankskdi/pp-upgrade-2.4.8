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

namespace AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Primary key field name
     *
     * @var string
     */
    protected $idFieldName = 'paymenttoken_id';

    /**
     * Initialize resource model collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \AutifyDigital\LloydscardnetPayment\Model\PaymentToken::class,
            \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken::class
        );
    }
}

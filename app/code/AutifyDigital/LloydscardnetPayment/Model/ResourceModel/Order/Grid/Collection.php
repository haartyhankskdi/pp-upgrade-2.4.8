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

namespace AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Order\Grid;

use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Sales order grid collection that exposes the Lloyds Cardnet AVS/Postcode/CVV checks.
 */
class Collection extends OrderGridCollection
{
    /**
     * Lloyds Cardnet payments table.
     */
    private const PAYMENTS_TABLE = 'autify_lloydscardnetpayment_payments';

    /**
     * Join the latest Lloyds payment row before the grid filters are rendered.
     *
     * Running this in _renderFiltersBefore() guarantees the joined columns and
     * their filter maps are available for both the count and the data queries.
     *
     * @return $this
     */
    protected function _renderFiltersBefore()
    {
        $this->joinLloydsAvsData();

        parent::_renderFiltersBefore();

        return $this;
    }

    /**
     * Left join the most recent Lloyds payment record for each order.
     *
     * @return void
     */
    private function joinLloydsAvsData(): void
    {
        $select = $this->getSelect();

        // Guard against the join being added twice.
        foreach (array_keys($select->getPart(Select::FROM)) as $alias) {
            if ($alias === 'lloyds_avs') {
                return;
            }
        }

        $connection = $this->getConnection();
        $paymentsTable = $this->getTable(self::PAYMENTS_TABLE);

        // Keep one row per order by selecting the latest payment record.
        $latestSelect = $connection->select()
            ->from($paymentsTable, ['order_id', 'payments_id' => new \Zend_Db_Expr('MAX(payments_id)')])
            ->where('order_id IS NOT NULL')
            ->group('order_id');

        $select->joinLeft(
            ['lloyds_avs_latest' => new \Zend_Db_Expr('(' . $latestSelect->assemble() . ')')],
            'lloyds_avs_latest.order_id = main_table.entity_id',
            []
        )->joinLeft(
            ['lloyds_avs' => $paymentsTable],
            'lloyds_avs.payments_id = lloyds_avs_latest.payments_id',
            [
                'street_match' => 'lloyds_avs.street_match',
                'postcode_match' => 'lloyds_avs.postcode_match',
                'cvv_match' => 'lloyds_avs.cvv_match',
                'brand' => 'lloyds_avs.brand',
                'payment_method' => 'lloyds_avs.payment_method',
            ]
        );

        $this->addFilterToMap('street_match', 'lloyds_avs.street_match');
        $this->addFilterToMap('postcode_match', 'lloyds_avs.postcode_match');
        $this->addFilterToMap('cvv_match', 'lloyds_avs.cvv_match');
    }
}

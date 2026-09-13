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

namespace AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\Grid;

use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Lloyds Cardnet transaction grid collection exposing the customer name and email.
 */
class Collection extends SearchResult
{
    /**
     * Sales order table holding the customer identity.
     */
    private const SALES_ORDER_TABLE = 'sales_order';

    /**
     * Customer name is stored as separate first/last columns on the order.
     */
    private const CUSTOMER_NAME_EXPR = "TRIM(CONCAT_WS(' ', sales_order.customer_firstname, sales_order.customer_lastname))";

    /**
     * Join the order before the grid filters are rendered.
     *
     * Running this in _renderFiltersBefore() makes the joined columns and their
     * filter maps available to both the count and the data queries.
     *
     * @return $this
     */
    protected function _renderFiltersBefore()
    {
        $this->joinCustomerData();

        parent::_renderFiltersBefore();

        return $this;
    }

    /**
     * Left join the order that this payment belongs to.
     *
     * @return void
     */
    private function joinCustomerData(): void
    {
        $select = $this->getSelect();

        foreach (array_keys($select->getPart(Select::FROM)) as $alias) {
            if ($alias === 'sales_order') {
                return;
            }
        }

        // Left join so payments with no order (abandoned/failed attempts) are still listed.
        $select->joinLeft(
            ['sales_order' => $this->getTable(self::SALES_ORDER_TABLE)],
            'sales_order.entity_id = main_table.order_id',
            [
                'customer_email' => 'sales_order.customer_email',
                'customer_name' => new \Zend_Db_Expr(self::CUSTOMER_NAME_EXPR),
            ]
        );

        $this->addFilterToMap('customer_email', 'sales_order.customer_email');
        $this->addFilterToMap('customer_name', new \Zend_Db_Expr(self::CUSTOMER_NAME_EXPR));

        // sales_order carries columns of the same name, so these would filter as ambiguous.
        $this->addFilterToMap('payments_id', 'main_table.payments_id');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('brand', 'main_table.brand');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');
    }
}

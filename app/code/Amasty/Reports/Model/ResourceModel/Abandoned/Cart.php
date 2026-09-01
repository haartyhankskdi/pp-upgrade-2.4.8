<?php

namespace Amasty\Reports\Model\ResourceModel\Abandoned;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Cart extends AbstractDb
{
    public const MAIN_TABLE = 'amasty_reports_abandoned_cart';
    public const ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const STORE_ID = 'store_id';
    public const CUSTOMER_NAME = 'customer_name';
    public const CUSTOMER_ID = 'customer_id';
    public const COUPON_CODE = 'coupon_code';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const ITEMS_QTY = 'items_qty';
    public const PRODUCTS = 'products';
    public const GRAND_TOTAL = 'grand_total';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID);
    }
}

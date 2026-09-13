<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model\ResourceModel\Banner;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mageplaza\PromoBanner\Model\Banner;

/**
 * Class Collection
 *
 * @package Mageplaza\PromoBanner\Model\ResourceModel\Banner
 */
class Collection extends AbstractCollection
{
    /**
     * ID Field Name
     *
     * @var string
     */
    protected $_idFieldName = 'banner_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Banner::class, \Mageplaza\PromoBanner\Model\ResourceModel\Banner::class);
    }

    /**
     * @param $customerGroup
     * @param $storeId
     * @param $date
     *
     * @return $this
     */
    public function addActiveFilter($customerGroup = null, $storeId = null, $date = null)
    {
        $this->addFieldToFilter('status', true)->setOrder('priority', Select::SQL_ASC);

        if (isset($customerGroup)) {
            $this->addFieldToFilter('customer_group_ids', ['finset' => $customerGroup]);
        }
        if (isset($storeId)) {
            $this->addFieldToFilter(['store_ids', 'store_ids'], [
                ['finset' => $storeId],
                ['finset' => 0]
            ]);
        }
        if (isset($date)) {
            $this->addFieldToFilter('from_date', ['lteq' => $date])
                ->addFieldToFilter('to_date', [
                    ['null' => null],
                    ['gteq' => $date]
                ]);
        }

        return $this;
    }

    /**
     * @param $productId
     *
     * @return $this
     */
    public function getCollectionByProductId($productId)
    {
        $this->getSelect()->joinLeft(
            ['promobanner_index' => $this->getTable('mageplaza_promobanner_actions_index')],
            'main_table.banner_id = promobanner_index.banner_id',
            ['product_id']
        )->where('page = 0 OR (show_product_page = 1 AND product_id = ?)', $productId)
            ->group('main_table.banner_id');

        return $this;
    }

    /**
     * @param $position
     *
     * @return $this
     */
    public function addPositionToFilter($position)
    {
        $this->addFieldToFilter('position', $position);

        return $this;
    }
}

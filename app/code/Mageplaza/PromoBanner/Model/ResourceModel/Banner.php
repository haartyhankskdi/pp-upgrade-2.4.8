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

namespace Mageplaza\PromoBanner\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mageplaza\PromoBanner\Helper\Data;
use Zend_Serializer_Exception;

/**
 * Class Banner
 *
 * @package Mageplaza\PromoBanner\Model\ResourceModel
 */
class Banner extends AbstractDb
{
    /**
     * Cache tag
     *
     * @var string
     */
    const CACHE_TAG = 'mageplaza_promobanner_banners';

    /**
     * Date model
     *
     * @var DateTime
     */
    protected $_date;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Banner constructor.
     *
     * @param Context $context
     * @param DateTime $date
     * @param Data $helperData
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        DateTime $date,
        Data $helperData,
        $connectionName = null
    ) {
        $this->_date      = $date;
        $this->helperData = $helperData;

        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('mageplaza_promobanner_banners', 'banner_id');
    }

    /**
     * @param AbstractModel $object
     *
     * @return AbstractDb
     * @throws Zend_Serializer_Exception
     */
    protected function _beforeSave(AbstractModel $object)
    {
        //set default Update At and Create At time post
        $object->setData('update_at', $this->_date->date());
        if ($object->isObjectNew()) {
            $object->setData('create_at', $this->_date->date());
        }

        $storeIds = $object->getData('store_ids');
        if (is_array($storeIds)) {
            $object->setData('store_ids', implode(',', $storeIds));
        }

        $groupIds = $object->getData('customer_group_ids');
        if (is_array($groupIds)) {
            $object->setData('customer_group_ids', implode(',', $groupIds));
        }

        $pageType = $object->getData('page_type');
        if (is_array($pageType)) {
            $object->setData('page_type', implode(',', $pageType));
        }

        $sliderImages = $object->getData('slider_images');
        if ($sliderImages && is_array($sliderImages)) {
            $object->setData('slider_images', $this->helperData->serialize($sliderImages));
        } else {
            $object->setData('slider_images', $this->helperData->serialize([]));
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param AbstractModel $object
     *
     * @return $this|AbstractDb
     * @throws Zend_Serializer_Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        if ($object->getData('slider_images') !== null) {
            $object->setData('slider_images', $this->helperData->unserialize($object->getData('slider_images')));
        }

        return $this;
    }

    /**
     * update database
     *
     * @param string $tableName
     * @param array $data
     *
     * @return void
     */
    public function updateMultipleData($tableName, $data = [])
    {
        $table = $this->getTable($tableName);
        if ($table && !empty($data)) {
            $this->getConnection()->insertMultiple($table, $data);
        }
    }

    /**
     * delete database
     *
     * @param string $tableName
     * @param array $where
     *
     * @return void
     */
    public function deleteMultipleData($tableName, $where = [])
    {
        $table = $this->getTable($tableName);
        if ($table && !empty($where)) {
            $this->getConnection()->delete($table, $where);
        }
    }

    /**
     * @param $id
     */
    public function deleteActionIndex($id)
    {
        $this->deleteMultipleData('mageplaza_promobanner_actions_index', ['banner_id = ?' => $id]);
    }

    /**
     * @param $data
     */
    public function insertActionIndex($data)
    {
        $this->updateMultipleData('mageplaza_promobanner_actions_index', $data);
    }
}

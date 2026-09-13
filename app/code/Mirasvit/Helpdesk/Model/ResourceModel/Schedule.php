<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model\ResourceModel;

use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\PageCache\Cache;

class Schedule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $dateFilter;

    protected $context;

    protected $resourcePrefix;

    private $pageCache;

    public function __construct(
        DateFilter $dateFilter,
        Context    $context,
        Cache      $pageCache,
        $resourcePrefix = null
    )
    {
        $this->dateFilter     = $dateFilter;
        $this->context        = $context;
        $this->pageCache      = $pageCache;
        $this->resourcePrefix = $resourcePrefix;

        parent::__construct($context, $resourcePrefix);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('mst_helpdesk_schedule', 'schedule_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return \Magento\Framework\Model\AbstractModel|\Mirasvit\Helpdesk\Model\Schedule
     */
    protected function loadStoreIds(\Magento\Framework\Model\AbstractModel $object)
    {
        /* @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('mst_helpdesk_schedule_store'))
            ->where('whs_schedule_id = ?', $object->getId());
        if ($data = $connection->fetchAll($select)) {
            $array = [];
            foreach ($data as $row) {
                $array[] = $row['whs_store_id'];
            }
            $object->setData('store_ids', $array);
        }

        return $object;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Schedule $object
     * @return void
     */
    protected function saveStoreIds($object)
    {
        /* @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        $condition = $connection->quoteInto('whs_schedule_id = ?', $object->getId());
        $connection->delete($this->getTable('mst_helpdesk_schedule_store'), $condition);
        foreach ((array) $object->getData('store_ids') as $id) {
            $objArray = [
                'whs_schedule_id' => $object->getId(),
                'whs_store_id' => $id,
            ];
            $connection->insert(
                $this->getTable('mst_helpdesk_schedule_store'),
                $objArray
            );
        }
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        if (!$object->getIsMassDelete()) {
            $this->loadStoreIds($object);
        }

        return parent::_afterLoad($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        if (!$object->getId()) {
            $object->setCreatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }
        $object->setUpdatedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        $activeFrom = $object->getData('active_from');
        $activeFrom = $this->dateFilter->filter($activeFrom);
        if ($activeFrom && $object->dataHasChangedFor('active_from')) {
            $date = (new \DateTime($activeFrom, new \DateTimeZone($object->getTimezone())))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $object->setActiveFrom($date);
        }
        $activeTo = $object->getData('active_to');
        $activeTo = $this->dateFilter->filter($activeTo);
        if ($activeTo && $object->dataHasChangedFor('active_to')) {
            $date = (new \DateTime($activeTo, new \DateTimeZone($object->getTimezone())))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
            $object->setActiveTo($date);
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        if (!$object->getIsMassStatus()) {
            $this->saveStoreIds($object);
        }

        $this->pageCache->getFrontend()->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['helpdesk_schedule_block']);

        return parent::_afterSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var  \Mirasvit\Helpdesk\Model\Schedule $object */
        if (!$object->getIsMassStatus()) {
            $object->unsetData('store_ids');
            $this->saveStoreIds($object);
        }

        return parent::_afterSave($object);
    }

    /************************/
}

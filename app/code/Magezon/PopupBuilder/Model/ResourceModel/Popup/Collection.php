<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model\ResourceModel\Popup;

use Magento\Store\Model\Store;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'popup_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'popupbuilder_popup_collection';

    /**
     * Event object name
     *
     * @var string
     */
    protected $_eventObject = 'popup_collection';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory
     * @param \Psr\Log\LoggerInterface                                     $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }
    
    /**
     * Set resource model and determine field mapping
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magezon\PopupBuilder\Model\Popup::class,
            \Magezon\PopupBuilder\Model\ResourceModel\Popup::class
        );
        $this->_map['fields']['store']         = 'store_table.store_id';
        $this->_map['fields']['popup_id']      = 'main_table.popup_id';
        $this->_map['fields']['customergroup'] = 'customergroup_table.customer_group_id';
        $this->_map['fields']['salerule']      = 'salerule_table.rule_id';
    }

    /**
     * Before collection load
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        $this->_eventManager->dispatch($this->_eventPrefix . '_load_before', [$this->_eventObject => $this]);
        return parent::_beforeLoad();
    }

    /**
     * After collection load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $this->_eventManager->dispatch($this->_eventPrefix . '_load_after', [$this->_eventObject => $this]);
        $this->performAfterLoad('mgz_popupbuilder_popup_store', 'popup_id', 'store_id');
        $this->performCustomerGroupAfterLoad('mgz_popupbuilder_popup_customer_group', 'popup_id', 'customer_group_id');
        $this->performSaleRulesAfterLoad('mgz_popupbuilder_popup_salesrule', 'popup_id', 'rule_id');
        return parent::_afterLoad();
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->performAddStoreFilter($store, $withAdmin);
        return $this;
    }

    /**
     * Perform adding filter by store
     *
     * @param int|array|Store $store
     * @param bool $withAdmin
     * @return void
     */
    protected function performAddStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof Store) {
            $store = [$store->getId()];
        }

        if (!is_array($store)) {
            $store = [$store];
        }

        if ($withAdmin) {
            $store[] = Store::DEFAULT_STORE_ID;
        }

        $this->addFilter('store', ['in' => $store], 'public');
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $this->joinStoreRelationTable('mgz_popupbuilder_popup_store', 'popup_id');
        $this->joinCustomerGroupRelationTable('customergroup_table', 'mgz_popupbuilder_popup_customer_group', 'popup_id');
        $this->joinSaleRulesRelationTable('salerule_table', 'mgz_popupbuilder_popup_salesrule', 'popup_id');
    }

    /**
     * Join store relation table if there is store filter
     *
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function joinCustomerGroupRelationTable($alias, $tableName, $linkField)
    {
        if ($this->getFilter('customergroup')) {
            $this->getSelect()->join(
                [$alias => $this->getTable($tableName)],
                'main_table.' . $linkField . ' = ' . $alias . '.' . $linkField,
                []
            )->group(
                'main_table.' . $linkField
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function joinSaleRulesRelationTable($alias, $tableName, $linkField)
    {
        if ($this->getFilter('salerule')) {
            $this->getSelect()->join(
                [$alias => $this->getTable($tableName)],
                'main_table.' . $linkField . ' = ' . $alias . '.' . $linkField,
                []
            )->group(
                'main_table.' . $linkField
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * Join store relation table if there is store filter
     *
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function joinStoreRelationTable($tableName, $linkField)
    {
        if ($this->getFilter('store')) {
            $this->getSelect()->join(
                ['store_table' => $this->getTable($tableName)],
                'main_table.' . $linkField . ' = store_table.' . $linkField,
                []
            )->group(
                'main_table.' . $linkField
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * Perform operations after collection load
     *
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function performAfterLoad($tableName, $linkField, $field)
    {
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(['popupbuilder_entity_store' => $this->getTable($tableName)])
                ->where('popupbuilder_entity_store.' . $linkField . ' IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $storesData = [];
                foreach ($result as $storeData) {
                    $storesData[$storeData[$linkField]][] = $storeData[$field];
                }
                foreach ($this as &$item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($storesData[$linkedId])) {
                        continue;
                    }
                    $storeIdKey = array_search(Store::DEFAULT_STORE_ID, $storesData[$linkedId], true);
                    if ($storeIdKey !== false) {
                        $stores = $this->storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                        $storeCode = key($stores);
                    } else {
                        $storeId = current($storesData[$linkedId]);
                        $storeCode = $this->storeManager->getStore($storeId)->getCode();
                    }
                    $item->setData($field, $storesData[$linkedId]);
                }
            }
        }
    }

    /**
     * Perform operations after collection load
     *
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function performCustomerGroupAfterLoad($tableName, $linkField, $field)
    {
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from($this->getTable($tableName))->where($linkField . ' IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $data = [];
                foreach ($result as $item) {
                    $data[$item[$linkField]][] = $item[$field];
                }
                foreach ($this as &$item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($data[$linkedId])) continue;
                    $item->setData($field, $data[$linkedId]);
                }
            }
        }
    }

    /**
     * Perform operations after collection load
     *
     * @param string $tableName
     * @param string|null $linkField
     * @return void
     */
    protected function performSaleRulesAfterLoad($tableName, $linkField, $field)
    {
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from($this->getTable($tableName))->where($linkField . ' IN (?)', $linkedIds);
            $result = $connection->fetchAll($select);
            if ($result) {
                $data = [];
                foreach ($result as $item) {
                    $data[$item[$linkField]][] = $item[$field];
                }
                foreach ($this as &$item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($data[$linkedId])) continue;
                    $item->setData($field, $data[$linkedId]);
                }
            }
        }
    }

    /**
     * Add active category filter
     *
     * @return $this
     */
    public function addIsActiveFilter()
    {
        $this->addFieldToFilter('is_active', 1);
        $this->_eventManager->dispatch(
            $this->_eventPrefix . '_add_is_active_filter',
            [$this->_eventObject => $this]
        );
        return $this;
    }
}

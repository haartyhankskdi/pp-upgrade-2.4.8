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

namespace Magezon\PopupBuilder\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\AbstractModel;
use Magezon\PopupBuilder\Api\Data\PopupInterface;

class Popup extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon     $resourceCoupon
     * @param string                                            $connectionName
     * @param \Magento\Framework\DataObject|null                $associatedEntityMapInstance
     * @param MetadataPool|null                                 $metadataPool
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\SalesRule\Model\ResourceModel\Coupon $resourceCoupon,
        $connectionName = null,
        \Magento\Framework\DataObject $associatedEntityMapInstance = null,
        MetadataPool $metadataPool = null
    ) {
        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()->get(MetadataPool::class);
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mgz_popupbuilder_popup', 'popup_id');
    }

    /**
     * Load an object
     *
     * @param AbstractModel $object
     * @param mixed $value
     * @param string $field field to load by (defaults to model id)
     * @return $this
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $this->getEntityManager()->load($object, $value);
        return $this;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->getEntityManager()->save($object);
        return $this;
    }

    /**
     * Delete the object
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function delete(AbstractModel $object)
    {
        $this->getEntityManager()->delete($object);
        return $this;
    }

    /**
     * @return \Magento\Framework\EntityManager\EntityManager
     * @deprecated 100.1.0
     */
    private function getEntityManager()
    {
        if (null === $this->entityManager) {
            $this->entityManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\EntityManager\EntityManager::class);
        }
        return $this->entityManager;
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $id
     * @return array
     */
    public function lookupStoreIds($id)
    {
        $connection = $this->getConnection();

        $entityMetadata = $this->metadataPool->getMetadata(PopupInterface::class);
        $linkField      = $entityMetadata->getLinkField();

        $select = $connection->select()
            ->from(['cbs' => $this->getTable('mgz_popupbuilder_popup_store')], 'store_id')
            ->join(
                ['cb' => $this->getMainTable()],
                'cbs.' . $linkField . ' = cb.' . $linkField,
                []
            )
            ->where('cb.' . $entityMetadata->getIdentifierField() . ' = :popup_id');

        return $connection->fetchCol($select, ['popup_id' => (int)$id]);
    }

    /**
     * Get customer groups to which specified item is assigned
     *
     * @param int $popupId
     * @return array
     */
    public function lookupCustomerGroups($popupId)
    {
        $connection     = $this->getConnection();
        $entityMetadata = $this->metadataPool->getMetadata(PopupInterface::class);
        $linkField      = $entityMetadata->getLinkField();

        $select = $connection->select()
            ->from(['mppcg' => $this->getTable('mgz_popupbuilder_popup_customer_group')], 'customer_group_id')
            ->join(
                ['cp' => $this->getMainTable()],
                'mppcg.popup_id = cp.' . $linkField,
                []
            )
            ->where('cp.' . $entityMetadata->getIdentifierField() . ' = :popup_id');

        return $connection->fetchCol($select, ['popup_id' => (int)$popupId]);
    }

    /**
     * Get sale rules to which specified item is assigned
     *
     * @param int $popupId
     * @return array
     */
    public function lookupSaleRules($popupId)
    {
        $connection     = $this->getConnection();
        $entityMetadata = $this->metadataPool->getMetadata(PopupInterface::class);
        $linkField      = $entityMetadata->getLinkField();

        $select = $connection->select()
            ->from(['mpps' => $this->getTable('mgz_popupbuilder_popup_salesrule')], 'rule_id')
            ->join(
                ['cp' => $this->getMainTable()],
                'mpps.popup_id = cp.' . $linkField,
                []
            )
            ->where('cp.' . $entityMetadata->getIdentifierField() . ' = :popup_id');

        return $connection->fetchCol($select, ['popup_id' => (int)$popupId]);
    }
}

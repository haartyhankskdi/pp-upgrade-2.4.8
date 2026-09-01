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

namespace Magezon\PopupBuilder\Model\ResourceModel\Popup\Relation\CustomerGroup;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magezon\PopupBuilder\Api\Data\PopupInterface;
use Magezon\PopupBuilder\Model\ResourceModel\Popup;
use Magento\Framework\EntityManager\MetadataPool;

class SaveHandler implements ExtensionInterface
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var Popup
     */
    protected $resourcePopup;

    /**
     * @param MetadataPool $metadataPool
     * @param Popup         $resourcePopup
     */
    public function __construct(
        MetadataPool $metadataPool,
        Popup $resourcePopup
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourcePopup = $resourcePopup;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        $entityMetadata = $this->metadataPool->getMetadata(PopupInterface::class);
        $linkField      = $entityMetadata->getLinkField();
        $connection     = $entityMetadata->getEntityConnection();
        $oldGroups      = $this->resourcePopup->lookupCustomerGroups((int)$entity->getId());
        $newGroups      = (array)$entity->getCustomerGroupId();

        $table  = $this->resourcePopup->getTable('mgz_popupbuilder_popup_customer_group');
        $delete = array_diff($oldGroups, $newGroups);
        if ($delete) {
            $where = [
                'popup_id = ?' => (int)$entity->getData($linkField),
                'customer_group_id IN (?)' => $delete
            ];
            $connection->delete($table, $where);
        }

        $insert = array_diff($newGroups, $oldGroups);
        if ($insert) {
            $data = [];
            foreach ($insert as $k => $groupId) {
                if ($groupId !== '') {
                    $data[] = [
                        'popup_id' => (int)$entity->getData($linkField),
                        'customer_group_id' => (int)$groupId
                    ];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($table, $data);
            }
        }

        return $entity;
    }
}

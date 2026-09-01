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

namespace Magezon\PopupBuilder\Model\ResourceModel\Popup\Relation\Salerule;

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
        $linkField = $entityMetadata->getLinkField();

        $connection = $entityMetadata->getEntityConnection();

        $oldRules = $this->resourcePopup->lookupSaleRules((int)$entity->getId());
        $newRules = (array)$entity->getSaleruleId();

        $table = $this->resourcePopup->getTable('mgz_popupbuilder_popup_salesrule');

        $delete = array_diff($oldRules, $newRules);
        if ($delete) {
            $where = [
                $linkField . ' = ?' => (int)$entity->getData($linkField),
                'rule_id IN (?)' => $delete,
            ];
            $connection->delete($table, $where);
        }

        $insert = array_diff($newRules, $oldRules);
        if ($insert) {
            $data = [];
            foreach ($insert as $ruleId) {
                $data[] = [
                    $linkField => (int)$entity->getData($linkField),
                    'rule_id' => (int)$ruleId
                ];
            }
            $connection->insertMultiple($table, $data);
        }

        return $entity;
    }
}

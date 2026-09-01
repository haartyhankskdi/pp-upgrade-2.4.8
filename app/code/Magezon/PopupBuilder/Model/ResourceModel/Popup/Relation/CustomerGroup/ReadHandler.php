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

use Magezon\PopupBuilder\Model\ResourceModel\Popup;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

class ReadHandler implements ExtensionInterface
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var Popup
     */
    protected $popupResource;

    /**
     * @param MetadataPool $metadataPool
     * @param Popup $popupResource
     */
    public function __construct(
        MetadataPool $metadataPool,
        Popup $popupResource
    ) {
        $this->metadataPool = $metadataPool;
        $this->popupResource = $popupResource;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return object
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getId()) {
            $customerGroups = $this->popupResource->lookupCustomerGroups((int)$entity->getId());
            $entity->setData('customer_group_id', $customerGroups);
        }
        return $entity;
    }
}

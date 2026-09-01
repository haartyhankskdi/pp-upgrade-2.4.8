<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Model;

use Haartyhanks\LNAPI\Api\Data\EntityInterface;
use Haartyhanks\LNAPI\Api\Data\EntityInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class Entity extends \Magento\Framework\Model\AbstractModel
{

    protected $_eventPrefix = 'haartyhanks_lnapi_entity';
    protected $entityDataFactory;

    protected $dataObjectHelper;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param EntityInterfaceFactory $entityDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Haartyhanks\LNAPI\Model\ResourceModel\Entity $resource
     * @param \Haartyhanks\LNAPI\Model\ResourceModel\Entity\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        EntityInterfaceFactory $entityDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Haartyhanks\LNAPI\Model\ResourceModel\Entity $resource,
        \Haartyhanks\LNAPI\Model\ResourceModel\Entity\Collection $resourceCollection,
        array $data = []
    ) {
        $this->entityDataFactory = $entityDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve entity model with entity data
     * @return EntityInterface
     */
    public function getDataModel()
    {
        $entityData = $this->getData();
        
        $entityDataObject = $this->entityDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $entityDataObject,
            $entityData,
            EntityInterface::class
        );
        
        return $entityDataObject;
    }
}


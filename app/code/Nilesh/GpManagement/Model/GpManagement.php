<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Model;

use Magento\Framework\Api\DataObjectHelper;
use Nilesh\GpManagement\Api\Data\GpManagementInterface;
use Nilesh\GpManagement\Api\Data\GpManagementInterfaceFactory;

class GpManagement extends \Magento\Framework\Model\AbstractModel
{

    protected $dataObjectHelper;

    protected $gpmanagementDataFactory;

    protected $_eventPrefix = 'nilesh_gpmanagement_gpmanagement';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param GpManagementInterfaceFactory $gpmanagementDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Nilesh\GpManagement\Model\ResourceModel\GpManagement $resource
     * @param \Nilesh\GpManagement\Model\ResourceModel\GpManagement\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        GpManagementInterfaceFactory $gpmanagementDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Nilesh\GpManagement\Model\ResourceModel\GpManagement $resource,
        \Nilesh\GpManagement\Model\ResourceModel\GpManagement\Collection $resourceCollection,
        array $data = []
    ) {
        $this->gpmanagementDataFactory = $gpmanagementDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve gpmanagement model with gpmanagement data
     * @return GpManagementInterface
     */
    public function getDataModel()
    {
        $gpmanagementData = $this->getData();
        
        $gpmanagementDataObject = $this->gpmanagementDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $gpmanagementDataObject,
            $gpmanagementData,
            GpManagementInterface::class
        );
        
        return $gpmanagementDataObject;
    }
}


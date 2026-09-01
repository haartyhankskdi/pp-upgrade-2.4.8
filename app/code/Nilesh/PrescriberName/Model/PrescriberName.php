<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Model;

use Magento\Framework\Api\DataObjectHelper;
use Nilesh\PrescriberName\Api\Data\PrescriberNameInterface;
use Nilesh\PrescriberName\Api\Data\PrescriberNameInterfaceFactory;

class PrescriberName extends \Magento\Framework\Model\AbstractModel
{

    protected $prescribernameDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'nilesh_prescribername_prescribername';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PrescriberNameInterfaceFactory $prescribernameDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Nilesh\PrescriberName\Model\ResourceModel\PrescriberName $resource
     * @param \Nilesh\PrescriberName\Model\ResourceModel\PrescriberName\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        PrescriberNameInterfaceFactory $prescribernameDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Nilesh\PrescriberName\Model\ResourceModel\PrescriberName $resource,
        \Nilesh\PrescriberName\Model\ResourceModel\PrescriberName\Collection $resourceCollection,
        array $data = []
    ) {
        $this->prescribernameDataFactory = $prescribernameDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve prescribername model with prescribername data
     * @return PrescriberNameInterface
     */
    public function getDataModel()
    {
        $prescribernameData = $this->getData();
        
        $prescribernameDataObject = $this->prescribernameDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $prescribernameDataObject,
            $prescribernameData,
            PrescriberNameInterface::class
        );
        
        return $prescribernameDataObject;
    }
}


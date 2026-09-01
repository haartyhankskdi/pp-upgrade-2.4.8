<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Model;

use Magento\Framework\Api\DataObjectHelper;
use Sachin\Customer\Api\Data\AgeverificationInterface;
use Sachin\Customer\Api\Data\AgeverificationInterfaceFactory;

class Ageverification extends \Magento\Framework\Model\AbstractModel
{

    protected $_eventPrefix = 'sachin_customer_ageverification';
    protected $ageverificationDataFactory;

    protected $dataObjectHelper;


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AgeverificationInterfaceFactory $ageverificationDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Sachin\Customer\Model\ResourceModel\Ageverification $resource
     * @param \Sachin\Customer\Model\ResourceModel\Ageverification\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        AgeverificationInterfaceFactory $ageverificationDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Sachin\Customer\Model\ResourceModel\Ageverification $resource,
        \Sachin\Customer\Model\ResourceModel\Ageverification\Collection $resourceCollection,
        array $data = []
    ) {
        $this->ageverificationDataFactory = $ageverificationDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve ageverification model with ageverification data
     * @return AgeverificationInterface
     */
    public function getDataModel()
    {
        $ageverificationData = $this->getData();
        
        $ageverificationDataObject = $this->ageverificationDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $ageverificationDataObject,
            $ageverificationData,
            AgeverificationInterface::class
        );
        
        return $ageverificationDataObject;
    }
}


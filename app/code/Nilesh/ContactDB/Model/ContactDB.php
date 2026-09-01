<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Model;

use Magento\Framework\Api\DataObjectHelper;
use Nilesh\ContactDB\Api\Data\ContactDBInterface;
use Nilesh\ContactDB\Api\Data\ContactDBInterfaceFactory;

class ContactDB extends \Magento\Framework\Model\AbstractModel
{

    protected $contactdbDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'nilesh_contactdb_contactdb';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ContactDBInterfaceFactory $contactdbDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Nilesh\ContactDB\Model\ResourceModel\ContactDB $resource
     * @param \Nilesh\ContactDB\Model\ResourceModel\ContactDB\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ContactDBInterfaceFactory $contactdbDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Nilesh\ContactDB\Model\ResourceModel\ContactDB $resource,
        \Nilesh\ContactDB\Model\ResourceModel\ContactDB\Collection $resourceCollection,
        array $data = []
    ) {
        $this->contactdbDataFactory = $contactdbDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve contactdb model with contactdb data
     * @return ContactDBInterface
     */
    public function getDataModel()
    {
        $contactdbData = $this->getData();
        
        $contactdbDataObject = $this->contactdbDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $contactdbDataObject,
            $contactdbData,
            ContactDBInterface::class
        );
        
        return $contactdbDataObject;
    }
}


<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\ContactDB\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Nilesh\ContactDB\Api\ContactDBRepositoryInterface;
use Nilesh\ContactDB\Api\Data\ContactDBInterfaceFactory;
use Nilesh\ContactDB\Api\Data\ContactDBSearchResultsInterfaceFactory;
use Nilesh\ContactDB\Model\ResourceModel\ContactDB as ResourceContactDB;
use Nilesh\ContactDB\Model\ResourceModel\ContactDB\CollectionFactory as ContactDBCollectionFactory;

class ContactDBRepository implements ContactDBRepositoryInterface
{

    protected $dataContactDBFactory;

    protected $searchResultsFactory;

    private $collectionProcessor;

    protected $resource;

    protected $contactDBFactory;

    protected $contactDBCollectionFactory;

    protected $extensibleDataObjectConverter;
    protected $dataObjectProcessor;

    protected $dataObjectHelper;

    private $storeManager;

    protected $extensionAttributesJoinProcessor;


    /**
     * @param ResourceContactDB $resource
     * @param ContactDBFactory $contactDBFactory
     * @param ContactDBInterfaceFactory $dataContactDBFactory
     * @param ContactDBCollectionFactory $contactDBCollectionFactory
     * @param ContactDBSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceContactDB $resource,
        ContactDBFactory $contactDBFactory,
        ContactDBInterfaceFactory $dataContactDBFactory,
        ContactDBCollectionFactory $contactDBCollectionFactory,
        ContactDBSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->contactDBFactory = $contactDBFactory;
        $this->contactDBCollectionFactory = $contactDBCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataContactDBFactory = $dataContactDBFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
    ) {
        /* if (empty($contactDB->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $contactDB->setStoreId($storeId);
        } */
        
        $contactDBData = $this->extensibleDataObjectConverter->toNestedArray(
            $contactDB,
            [],
            \Nilesh\ContactDB\Api\Data\ContactDBInterface::class
        );
        
        $contactDBModel = $this->contactDBFactory->create()->setData($contactDBData);
        
        try {
            $this->resource->save($contactDBModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the contactDB: %1',
                $exception->getMessage()
            ));
        }
        return $contactDBModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($contactDBId)
    {
        $contactDB = $this->contactDBFactory->create();
        $this->resource->load($contactDB, $contactDBId);
        if (!$contactDB->getId()) {
            throw new NoSuchEntityException(__('ContactDB with id "%1" does not exist.', $contactDBId));
        }
        return $contactDB->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->contactDBCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Nilesh\ContactDB\Api\Data\ContactDBInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Nilesh\ContactDB\Api\Data\ContactDBInterface $contactDB
    ) {
        try {
            $contactDBModel = $this->contactDBFactory->create();
            $this->resource->load($contactDBModel, $contactDB->getContactdbId());
            $this->resource->delete($contactDBModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ContactDB: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($contactDBId)
    {
        return $this->delete($this->get($contactDBId));
    }
}


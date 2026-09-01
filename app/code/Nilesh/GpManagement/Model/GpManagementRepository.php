<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GpManagement\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Nilesh\GpManagement\Api\Data\GpManagementInterfaceFactory;
use Nilesh\GpManagement\Api\Data\GpManagementSearchResultsInterfaceFactory;
use Nilesh\GpManagement\Api\GpManagementRepositoryInterface;
use Nilesh\GpManagement\Model\ResourceModel\GpManagement as ResourceGpManagement;
use Nilesh\GpManagement\Model\ResourceModel\GpManagement\CollectionFactory as GpManagementCollectionFactory;

class GpManagementRepository implements GpManagementRepositoryInterface
{

    protected $extensibleDataObjectConverter;
    protected $gpManagementCollectionFactory;

    protected $gpManagementFactory;

    protected $dataObjectHelper;

    protected $resource;

    private $storeManager;

    protected $dataGpManagementFactory;

    protected $searchResultsFactory;

    protected $dataObjectProcessor;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;


    /**
     * @param ResourceGpManagement $resource
     * @param GpManagementFactory $gpManagementFactory
     * @param GpManagementInterfaceFactory $dataGpManagementFactory
     * @param GpManagementCollectionFactory $gpManagementCollectionFactory
     * @param GpManagementSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceGpManagement $resource,
        GpManagementFactory $gpManagementFactory,
        GpManagementInterfaceFactory $dataGpManagementFactory,
        GpManagementCollectionFactory $gpManagementCollectionFactory,
        GpManagementSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->gpManagementFactory = $gpManagementFactory;
        $this->gpManagementCollectionFactory = $gpManagementCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataGpManagementFactory = $dataGpManagementFactory;
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
        \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
    ) {
        /* if (empty($gpManagement->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $gpManagement->setStoreId($storeId);
        } */
        
        $gpManagementData = $this->extensibleDataObjectConverter->toNestedArray(
            $gpManagement,
            [],
            \Nilesh\GpManagement\Api\Data\GpManagementInterface::class
        );
        
        $gpManagementModel = $this->gpManagementFactory->create()->setData($gpManagementData);
        
        try {
            $this->resource->save($gpManagementModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the gpManagement: %1',
                $exception->getMessage()
            ));
        }
        return $gpManagementModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($gpManagementId)
    {
        $gpManagement = $this->gpManagementFactory->create();
        $this->resource->load($gpManagement, $gpManagementId);
        if (!$gpManagement->getId()) {
            throw new NoSuchEntityException(__('GpManagement with id "%1" does not exist.', $gpManagementId));
        }
        return $gpManagement->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->gpManagementCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Nilesh\GpManagement\Api\Data\GpManagementInterface::class
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
        \Nilesh\GpManagement\Api\Data\GpManagementInterface $gpManagement
    ) {
        try {
            $gpManagementModel = $this->gpManagementFactory->create();
            $this->resource->load($gpManagementModel, $gpManagement->getGpmanagementId());
            $this->resource->delete($gpManagementModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the GpManagement: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($gpManagementId)
    {
        return $this->delete($this->get($gpManagementId));
    }
}


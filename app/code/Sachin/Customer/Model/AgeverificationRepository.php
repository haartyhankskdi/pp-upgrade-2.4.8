<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Sachin\Customer\Api\AgeverificationRepositoryInterface;
use Sachin\Customer\Api\Data\AgeverificationInterfaceFactory;
use Sachin\Customer\Api\Data\AgeverificationSearchResultsInterfaceFactory;
use Sachin\Customer\Model\ResourceModel\Ageverification as ResourceAgeverification;
use Sachin\Customer\Model\ResourceModel\Ageverification\CollectionFactory as AgeverificationCollectionFactory;

class AgeverificationRepository implements AgeverificationRepositoryInterface
{

    protected $dataObjectProcessor;

    protected $extensibleDataObjectConverter;
    private $collectionProcessor;

    protected $dataAgeverificationFactory;

    protected $searchResultsFactory;

    private $storeManager;

    protected $resource;

    protected $ageverificationCollectionFactory;

    protected $dataObjectHelper;

    protected $extensionAttributesJoinProcessor;

    protected $ageverificationFactory;


    /**
     * @param ResourceAgeverification $resource
     * @param AgeverificationFactory $ageverificationFactory
     * @param AgeverificationInterfaceFactory $dataAgeverificationFactory
     * @param AgeverificationCollectionFactory $ageverificationCollectionFactory
     * @param AgeverificationSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceAgeverification $resource,
        AgeverificationFactory $ageverificationFactory,
        AgeverificationInterfaceFactory $dataAgeverificationFactory,
        AgeverificationCollectionFactory $ageverificationCollectionFactory,
        AgeverificationSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->ageverificationFactory = $ageverificationFactory;
        $this->ageverificationCollectionFactory = $ageverificationCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataAgeverificationFactory = $dataAgeverificationFactory;
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
        \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
    ) {
        /* if (empty($ageverification->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $ageverification->setStoreId($storeId);
        } */
        
        $ageverificationData = $this->extensibleDataObjectConverter->toNestedArray(
            $ageverification,
            [],
            \Sachin\Customer\Api\Data\AgeverificationInterface::class
        );
        
        $ageverificationModel = $this->ageverificationFactory->create()->setData($ageverificationData);
        
        try {
            $this->resource->save($ageverificationModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the ageverification: %1',
                $exception->getMessage()
            ));
        }
        return $ageverificationModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($ageverificationId)
    {
        $ageverification = $this->ageverificationFactory->create();
        $this->resource->load($ageverification, $ageverificationId);
        if (!$ageverification->getId()) {
            throw new NoSuchEntityException(__('ageverification with id "%1" does not exist.', $ageverificationId));
        }
        return $ageverification->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->ageverificationCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Sachin\Customer\Api\Data\AgeverificationInterface::class
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
        \Sachin\Customer\Api\Data\AgeverificationInterface $ageverification
    ) {
        try {
            $ageverificationModel = $this->ageverificationFactory->create();
            $this->resource->load($ageverificationModel, $ageverification->getAgeverificationId());
            $this->resource->delete($ageverificationModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ageverification: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($ageverificationId)
    {
        return $this->delete($this->get($ageverificationId));
    }
}


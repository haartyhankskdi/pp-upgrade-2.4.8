<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberName\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Nilesh\PrescriberName\Api\Data\PrescriberNameInterfaceFactory;
use Nilesh\PrescriberName\Api\Data\PrescriberNameSearchResultsInterfaceFactory;
use Nilesh\PrescriberName\Api\PrescriberNameRepositoryInterface;
use Nilesh\PrescriberName\Model\ResourceModel\PrescriberName as ResourcePrescriberName;
use Nilesh\PrescriberName\Model\ResourceModel\PrescriberName\CollectionFactory as PrescriberNameCollectionFactory;

class PrescriberNameRepository implements PrescriberNameRepositoryInterface
{

    private $collectionProcessor;

    protected $resource;

    protected $dataPrescriberNameFactory;

    protected $extensibleDataObjectConverter;
    protected $searchResultsFactory;

    protected $dataObjectProcessor;

    private $storeManager;

    protected $prescriberNameFactory;

    protected $extensionAttributesJoinProcessor;

    protected $dataObjectHelper;

    protected $prescriberNameCollectionFactory;


    /**
     * @param ResourcePrescriberName $resource
     * @param PrescriberNameFactory $prescriberNameFactory
     * @param PrescriberNameInterfaceFactory $dataPrescriberNameFactory
     * @param PrescriberNameCollectionFactory $prescriberNameCollectionFactory
     * @param PrescriberNameSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourcePrescriberName $resource,
        PrescriberNameFactory $prescriberNameFactory,
        PrescriberNameInterfaceFactory $dataPrescriberNameFactory,
        PrescriberNameCollectionFactory $prescriberNameCollectionFactory,
        PrescriberNameSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->prescriberNameFactory = $prescriberNameFactory;
        $this->prescriberNameCollectionFactory = $prescriberNameCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPrescriberNameFactory = $dataPrescriberNameFactory;
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
        \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
    ) {
        /* if (empty($prescriberName->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $prescriberName->setStoreId($storeId);
        } */
        
        $prescriberNameData = $this->extensibleDataObjectConverter->toNestedArray(
            $prescriberName,
            [],
            \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface::class
        );
        
        $prescriberNameModel = $this->prescriberNameFactory->create()->setData($prescriberNameData);
        
        try {
            $this->resource->save($prescriberNameModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the prescriberName: %1',
                $exception->getMessage()
            ));
        }
        return $prescriberNameModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($prescriberNameId)
    {
        $prescriberName = $this->prescriberNameFactory->create();
        $this->resource->load($prescriberName, $prescriberNameId);
        if (!$prescriberName->getId()) {
            throw new NoSuchEntityException(__('PrescriberName with id "%1" does not exist.', $prescriberNameId));
        }
        return $prescriberName->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->prescriberNameCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface::class
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
        \Nilesh\PrescriberName\Api\Data\PrescriberNameInterface $prescriberName
    ) {
        try {
            $prescriberNameModel = $this->prescriberNameFactory->create();
            $this->resource->load($prescriberNameModel, $prescriberName->getPrescribernameId());
            $this->resource->delete($prescriberNameModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PrescriberName: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($prescriberNameId)
    {
        return $this->delete($this->get($prescriberNameId));
    }
}


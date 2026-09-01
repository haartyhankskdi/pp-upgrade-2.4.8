<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\GeneralQuestions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterfaceFactory;
use Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsSearchResultsInterfaceFactory;
use Nilesh\GeneralQuestions\Api\GeneralQuestionsRepositoryInterface;
use Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions as ResourceGeneralQuestions;
use Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\CollectionFactory as GeneralQuestionsCollectionFactory;

class GeneralQuestionsRepository implements GeneralQuestionsRepositoryInterface
{

    protected $extensibleDataObjectConverter;
    protected $generalQuestionsFactory;

    protected $dataGeneralQuestionsFactory;

    protected $dataObjectHelper;

    protected $resource;

    private $storeManager;

    protected $searchResultsFactory;

    protected $dataObjectProcessor;

    protected $generalQuestionsCollectionFactory;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;


    /**
     * @param ResourceGeneralQuestions $resource
     * @param GeneralQuestionsFactory $generalQuestionsFactory
     * @param GeneralQuestionsInterfaceFactory $dataGeneralQuestionsFactory
     * @param GeneralQuestionsCollectionFactory $generalQuestionsCollectionFactory
     * @param GeneralQuestionsSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceGeneralQuestions $resource,
        GeneralQuestionsFactory $generalQuestionsFactory,
        GeneralQuestionsInterfaceFactory $dataGeneralQuestionsFactory,
        GeneralQuestionsCollectionFactory $generalQuestionsCollectionFactory,
        GeneralQuestionsSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->generalQuestionsFactory = $generalQuestionsFactory;
        $this->generalQuestionsCollectionFactory = $generalQuestionsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataGeneralQuestionsFactory = $dataGeneralQuestionsFactory;
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
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
    ) {
        /* if (empty($generalQuestions->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $generalQuestions->setStoreId($storeId);
        } */
        
        $generalQuestionsData = $this->extensibleDataObjectConverter->toNestedArray(
            $generalQuestions,
            [],
            \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface::class
        );
        
        $generalQuestionsModel = $this->generalQuestionsFactory->create()->setData($generalQuestionsData);
        
        try {
            $this->resource->save($generalQuestionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the generalQuestions: %1',
                $exception->getMessage()
            ));
        }
        return $generalQuestionsModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($generalQuestionsId)
    {
        $generalQuestions = $this->generalQuestionsFactory->create();
        $this->resource->load($generalQuestions, $generalQuestionsId);
        if (!$generalQuestions->getId()) {
            throw new NoSuchEntityException(__('GeneralQuestions with id "%1" does not exist.', $generalQuestionsId));
        }
        return $generalQuestions->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->generalQuestionsCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface::class
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
        \Nilesh\GeneralQuestions\Api\Data\GeneralQuestionsInterface $generalQuestions
    ) {
        try {
            $generalQuestionsModel = $this->generalQuestionsFactory->create();
            $this->resource->load($generalQuestionsModel, $generalQuestions->getGeneralquestionsId());
            $this->resource->delete($generalQuestionsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the GeneralQuestions: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($generalQuestionsId)
    {
        return $this->delete($this->get($generalQuestionsId));
    }
}


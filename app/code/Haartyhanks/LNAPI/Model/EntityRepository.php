<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Model;

use Haartyhanks\LNAPI\Api\Data\EntityInterfaceFactory;
use Haartyhanks\LNAPI\Api\Data\EntitySearchResultsInterfaceFactory;
use Haartyhanks\LNAPI\Api\EntityRepositoryInterface;
use Haartyhanks\LNAPI\Model\ResourceModel\Entity as ResourceEntity;
use Haartyhanks\LNAPI\Model\ResourceModel\Entity\CollectionFactory as EntityCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class EntityRepository implements EntityRepositoryInterface
{

    protected $extensibleDataObjectConverter;
    private $collectionProcessor;

    protected $extensionAttributesJoinProcessor;

    protected $entityCollectionFactory;

    protected $dataObjectHelper;

    protected $searchResultsFactory;

    private $storeManager;

    protected $dataObjectProcessor;

    protected $resource;

    protected $dataEntityFactory;

    protected $entityFactory;


    /**
     * @param ResourceEntity $resource
     * @param EntityFactory $entityFactory
     * @param EntityInterfaceFactory $dataEntityFactory
     * @param EntityCollectionFactory $entityCollectionFactory
     * @param EntitySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceEntity $resource,
        EntityFactory $entityFactory,
        EntityInterfaceFactory $dataEntityFactory,
        EntityCollectionFactory $entityCollectionFactory,
        EntitySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->entityCollectionFactory = $entityCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataEntityFactory = $dataEntityFactory;
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
        \Haartyhanks\LNAPI\Api\Data\EntityInterface $entity
    ) {
        /* if (empty($entity->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $entity->setStoreId($storeId);
        } */
        
        $entityData = $this->extensibleDataObjectConverter->toNestedArray(
            $entity,
            [],
            \Haartyhanks\LNAPI\Api\Data\EntityInterface::class
        );
        
        $entityModel = $this->entityFactory->create()->setData($entityData);
        
        try {
            $this->resource->save($entityModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the entity: %1',
                $exception->getMessage()
            ));
        }
        return $entityModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($entityId)
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Entity with id "%1" does not exist.', $entityId));
        }
        return $entity->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->entityCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Haartyhanks\LNAPI\Api\Data\EntityInterface::class
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
        \Haartyhanks\LNAPI\Api\Data\EntityInterface $entity
    ) {
        try {
            $entityModel = $this->entityFactory->create();
            $this->resource->load($entityModel, $entity->getEntityId());
            $this->resource->delete($entityModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Entity: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->get($entityId));
    }
}


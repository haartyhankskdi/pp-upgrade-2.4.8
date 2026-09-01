<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Model;

use Kdi\ImageUpload\Api\Data\EntityInterface;
use Kdi\ImageUpload\Api\Data\EntityInterfaceFactory;
use Kdi\ImageUpload\Api\Data\EntitySearchResultsInterfaceFactory;
use Kdi\ImageUpload\Api\EntityRepositoryInterface;
use Kdi\ImageUpload\Model\ResourceModel\Entity as ResourceEntity;
use Kdi\ImageUpload\Model\ResourceModel\Entity\CollectionFactory as EntityCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class EntityRepository implements EntityRepositoryInterface
{

    /**
     * @var ResourceEntity
     */
    protected $resource;

    /**
     * @var EntityInterfaceFactory
     */
    protected $entityFactory;

    /**
     * @var Entity
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var EntityCollectionFactory
     */
    protected $entityCollectionFactory;


    /**
     * @param ResourceEntity $resource
     * @param EntityInterfaceFactory $entityFactory
     * @param EntityCollectionFactory $entityCollectionFactory
     * @param EntitySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceEntity $resource,
        EntityInterfaceFactory $entityFactory,
        EntityCollectionFactory $entityCollectionFactory,
        EntitySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->entityFactory = $entityFactory;
        $this->entityCollectionFactory = $entityCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(EntityInterface $entity)
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the entity: %1',
                $exception->getMessage()
            ));
        }
        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function get($entityId)
    {
        $entity = $this->entityFactory->create();
        $this->resource->load($entity, $entityId);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Entity with id "%1" does not exist.', $entityId));
        }
        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->entityCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity)
    {
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
     * @inheritDoc
     */
    public function deleteById($entityId)
    {
        return $this->delete($this->get($entityId));
    }
}


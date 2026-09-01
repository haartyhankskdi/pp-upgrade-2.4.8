<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Model;

use Kdi\AdvisePost\Api\AdvicePostRepositoryInterface;
use Kdi\AdvisePost\Api\Data\AdvicePostInterface;
use Kdi\AdvisePost\Api\Data\AdvicePostInterfaceFactory;
use Kdi\AdvisePost\Api\Data\AdvicePostSearchResultsInterfaceFactory;
use Kdi\AdvisePost\Model\ResourceModel\AdvicePost as ResourceAdvicePost;
use Kdi\AdvisePost\Model\ResourceModel\AdvicePost\CollectionFactory as AdvicePostCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AdvicePostRepository implements AdvicePostRepositoryInterface
{

    /**
     * @var AdvicePost
     */
    protected $searchResultsFactory;

    /**
     * @var ResourceAdvicePost
     */
    protected $resource;

    /**
     * @var AdvicePostInterfaceFactory
     */
    protected $advicePostFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var AdvicePostCollectionFactory
     */
    protected $advicePostCollectionFactory;


    /**
     * @param ResourceAdvicePost $resource
     * @param AdvicePostInterfaceFactory $advicePostFactory
     * @param AdvicePostCollectionFactory $advicePostCollectionFactory
     * @param AdvicePostSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceAdvicePost $resource,
        AdvicePostInterfaceFactory $advicePostFactory,
        AdvicePostCollectionFactory $advicePostCollectionFactory,
        AdvicePostSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->advicePostFactory = $advicePostFactory;
        $this->advicePostCollectionFactory = $advicePostCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(AdvicePostInterface $advicePost)
    {
        try {
            $this->resource->save($advicePost);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the advicePost: %1',
                $exception->getMessage()
            ));
        }
        return $advicePost;
    }

    /**
     * @inheritDoc
     */
    public function get($entity_id)
    {
        $advicePost = $this->advicePostFactory->create();
        $this->resource->load($advicePost, $entity_id);
        if (!$advicePost->getId()) {
            throw new NoSuchEntityException(__('AdvicePost with id "%1" does not exist.', $entity_id));
        }
        return $advicePost;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->advicePostCollectionFactory->create();
        
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
    public function delete(AdvicePostInterface $advicePost)
    {
        try {
            $advicePostModel = $this->advicePostFactory->create();
            $this->resource->load($advicePostModel, $advicePost->getAdvicepostId());
            $this->resource->delete($advicePostModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the AdvicePost: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($entity_id)
    {
        return $this->delete($this->get($entity_id));
    }
}


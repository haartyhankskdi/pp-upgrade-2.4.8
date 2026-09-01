<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface;
use Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterfaceFactory;
use Nilesh\PrescriberNotes\Api\Data\PrescriberNotesSearchResultsInterfaceFactory;
use Nilesh\PrescriberNotes\Api\PrescriberNotesRepositoryInterface;
use Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes as ResourcePrescriberNotes;
use Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\CollectionFactory as PrescriberNotesCollectionFactory;

class PrescriberNotesRepository implements PrescriberNotesRepositoryInterface
{

    /**
     * @var ResourcePrescriberNotes
     */
    protected $resource;

    /**
     * @var PrescriberNotes
     */
    protected $searchResultsFactory;

    /**
     * @var PrescriberNotesInterfaceFactory
     */
    protected $prescriberNotesFactory;

    /**
     * @var PrescriberNotesCollectionFactory
     */
    protected $prescriberNotesCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourcePrescriberNotes $resource
     * @param PrescriberNotesInterfaceFactory $prescriberNotesFactory
     * @param PrescriberNotesCollectionFactory $prescriberNotesCollectionFactory
     * @param PrescriberNotesSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourcePrescriberNotes $resource,
        PrescriberNotesInterfaceFactory $prescriberNotesFactory,
        PrescriberNotesCollectionFactory $prescriberNotesCollectionFactory,
        PrescriberNotesSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->prescriberNotesFactory = $prescriberNotesFactory;
        $this->prescriberNotesCollectionFactory = $prescriberNotesCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        PrescriberNotesInterface $prescriberNotes
    ) {
        try {
            $this->resource->save($prescriberNotes);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the prescriberNotes: %1',
                $exception->getMessage()
            ));
        }
        return $prescriberNotes;
    }

    /**
     * @inheritDoc
     */
    public function get($prescriberNotesId)
    {
        $prescriberNotes = $this->prescriberNotesFactory->create();
        $this->resource->load($prescriberNotes, $prescriberNotesId);
        if (!$prescriberNotes->getId()) {
            throw new NoSuchEntityException(__('PrescriberNotes with id "%1" does not exist.', $prescriberNotesId));
        }
        return $prescriberNotes;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->prescriberNotesCollectionFactory->create();
        
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
    public function delete(
        PrescriberNotesInterface $prescriberNotes
    ) {
        try {
            $prescriberNotesModel = $this->prescriberNotesFactory->create();
            $this->resource->load($prescriberNotesModel, $prescriberNotes->getPrescribernotesId());
            $this->resource->delete($prescriberNotesModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PrescriberNotes: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($prescriberNotesId)
    {
        return $this->delete($this->get($prescriberNotesId));
    }
}


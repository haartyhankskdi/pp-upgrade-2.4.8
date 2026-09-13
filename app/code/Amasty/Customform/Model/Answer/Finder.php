<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

use Amasty\Customform\Model\ResourceModel\Answer\Collection as AnswersCollection;
use Amasty\Customform\Model\ResourceModel\Answer\CollectionFactory as AnswersCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

class Finder implements FinderInterface
{
    public const DEFAULT_BATCH_SIZE = 500;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var AnswersCollection
     */
    private $collection;

    /**
     * @var AnswersCollectionFactory
     */
    private $answersCollectionFactory;

    /**
     * @var SearchCriteriaInterface|null
     */
    private $searchCriteria;

    /**
     * @var int
     */
    private $batchSize;

    public function __construct(
        CollectionProcessorInterface $collectionProcessor,
        AnswersCollectionFactory $answersCollectionFactory,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->answersCollectionFactory = $answersCollectionFactory;
        $this->batchSize = $batchSize;
    }

    private function getCollection(): AnswersCollection
    {
        if ($this->collection === null) {
            $this->collection = $this->answersCollectionFactory->create();
            $searchCriteria = $this->getSearchCriteria();

            if ($searchCriteria !== null) {
                $this->collectionProcessor->process($searchCriteria, $this->collection);
            }
        }

        return $this->collection;
    }

    public function isEmptyResult(): bool
    {
        return !$this->getResultsCount();
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria): void
    {
        $this->searchCriteria = $searchCriteria;
    }

    public function getResult(): iterable
    {
        $answersCollection = $this->getCollection();
        $answersCollection->setPageSize($this->batchSize);
        $lastPageNumber = $answersCollection->getLastPageNumber();

        for ($pageNumber = 1; $pageNumber <= $lastPageNumber; ++$pageNumber) {
            $batchCollection = clone $answersCollection;

            yield from $batchCollection->setCurPage($pageNumber);
        }
    }

    private function getSearchCriteria(): ?SearchCriteriaInterface
    {
        return $this->searchCriteria;
    }

    public function getResultsCount(): int
    {
        return $this->getCollection()->getSize();
    }
}

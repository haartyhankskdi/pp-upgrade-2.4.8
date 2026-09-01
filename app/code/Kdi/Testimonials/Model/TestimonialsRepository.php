<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Model;

use Kdi\Testimonials\Api\Data\TestimonialsInterface;
use Kdi\Testimonials\Api\Data\TestimonialsInterfaceFactory;
use Kdi\Testimonials\Api\Data\TestimonialsSearchResultsInterfaceFactory;
use Kdi\Testimonials\Api\TestimonialsRepositoryInterface;
use Kdi\Testimonials\Model\ResourceModel\Testimonials as ResourceTestimonials;
use Kdi\Testimonials\Model\ResourceModel\Testimonials\CollectionFactory as TestimonialsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class TestimonialsRepository implements TestimonialsRepositoryInterface
{

    /**
     * @var TestimonialsInterfaceFactory
     */
    protected $testimonialsFactory;

    /**
     * @var TestimonialsCollectionFactory
     */
    protected $testimonialsCollectionFactory;

    /**
     * @var Testimonials
     */
    protected $searchResultsFactory;

    /**
     * @var ResourceTestimonials
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceTestimonials $resource
     * @param TestimonialsInterfaceFactory $testimonialsFactory
     * @param TestimonialsCollectionFactory $testimonialsCollectionFactory
     * @param TestimonialsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceTestimonials $resource,
        TestimonialsInterfaceFactory $testimonialsFactory,
        TestimonialsCollectionFactory $testimonialsCollectionFactory,
        TestimonialsSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->testimonialsFactory = $testimonialsFactory;
        $this->testimonialsCollectionFactory = $testimonialsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(TestimonialsInterface $testimonials)
    {
        try {
            $this->resource->save($testimonials);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the testimonials: %1',
                $exception->getMessage()
            ));
        }
        return $testimonials;
    }

    /**
     * @inheritDoc
     */
    public function get($testimonialsId)
    {
        $testimonials = $this->testimonialsFactory->create();
        $this->resource->load($testimonials, $testimonialsId);
        if (!$testimonials->getId()) {
            throw new NoSuchEntityException(__('Testimonials with id "%1" does not exist.', $testimonialsId));
        }
        return $testimonials;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->testimonialsCollectionFactory->create();
        
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
    public function delete(TestimonialsInterface $testimonials)
    {
        try {
            $testimonialsModel = $this->testimonialsFactory->create();
            $this->resource->load($testimonialsModel, $testimonials->getTestimonialsId());
            $this->resource->delete($testimonialsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Testimonials: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($testimonialsId)
    {
        return $this->delete($this->get($testimonialsId));
    }
}


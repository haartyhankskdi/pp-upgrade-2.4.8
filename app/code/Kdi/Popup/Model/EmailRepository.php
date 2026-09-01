<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Popup\Model;

use Kdi\Popup\Api\Data\EmailInterfaceFactory;
use Kdi\Popup\Api\Data\EmailSearchResultsInterfaceFactory;
use Kdi\Popup\Api\EmailRepositoryInterface;
use Kdi\Popup\Model\ResourceModel\Email as ResourceEmail;
use Kdi\Popup\Model\ResourceModel\Email\CollectionFactory as EmailCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class EmailRepository implements EmailRepositoryInterface
{

    protected $extensibleDataObjectConverter;
    private $collectionProcessor;

    protected $extensionAttributesJoinProcessor;

    protected $emailFactory;

    protected $dataObjectHelper;

    protected $searchResultsFactory;

    private $storeManager;

    protected $dataObjectProcessor;

    protected $emailCollectionFactory;

    protected $dataEmailFactory;

    protected $resource;


    /**
     * @param ResourceEmail $resource
     * @param EmailFactory $emailFactory
     * @param EmailInterfaceFactory $dataEmailFactory
     * @param EmailCollectionFactory $emailCollectionFactory
     * @param EmailSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceEmail $resource,
        EmailFactory $emailFactory,
        EmailInterfaceFactory $dataEmailFactory,
        EmailCollectionFactory $emailCollectionFactory,
        EmailSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->emailFactory = $emailFactory;
        $this->emailCollectionFactory = $emailCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataEmailFactory = $dataEmailFactory;
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
        \Kdi\Popup\Api\Data\EmailInterface $email
    ) {
        /* if (empty($email->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $email->setStoreId($storeId);
        } */
        
        $emailData = $this->extensibleDataObjectConverter->toNestedArray(
            $email,
            [],
            \Kdi\Popup\Api\Data\EmailInterface::class
        );
        
        $emailModel = $this->emailFactory->create()->setData($emailData);
        
        try {
            $this->resource->save($emailModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the email: %1',
                $exception->getMessage()
            ));
        }
        return $emailModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($emailId)
    {
        $email = $this->emailFactory->create();
        $this->resource->load($email, $emailId);
        if (!$email->getId()) {
            throw new NoSuchEntityException(__('Email with id "%1" does not exist.', $emailId));
        }
        return $email->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->emailCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Kdi\Popup\Api\Data\EmailInterface::class
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
        \Kdi\Popup\Api\Data\EmailInterface $email
    ) {
        try {
            $emailModel = $this->emailFactory->create();
            $this->resource->load($emailModel, $email->getEmailId());
            $this->resource->delete($emailModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Email: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($emailId)
    {
        return $this->delete($this->get($emailId));
    }
}


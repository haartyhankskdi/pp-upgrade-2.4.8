<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model;

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenSearchResultsInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenSearchResultsInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentTokenRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken as ResourcePaymentToken;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory; // phpcs:ignore
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class PaymentTokenRepository implements PaymentTokenRepositoryInterface
{
    /**
     * @var ResourcePaymentToken
     */
    protected $resource;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory
     */
    protected $paymentTokenCollectionFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * PaymentTokenRepository constructor.
     *
     * @param ResourcePaymentToken $resource
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     * @param PaymentTokenSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourcePaymentToken $resource,
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        PaymentTokenSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Save payment token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return PaymentTokenInterface
     * @throws CouldNotSaveException
     */
    public function save(PaymentTokenInterface $paymentToken): PaymentTokenInterface
    {
        try {
            // Concrete model extends AbstractModel (for resource->save) and implements PaymentTokenInterface
            /** @var \AutifyDigital\LloydscardnetPayment\Model\PaymentToken $paymentTokenModel */
            $paymentTokenModel = $paymentToken;
            $this->resource->save($paymentTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the paymentToken: %1',
                $exception->getMessage()
            ));
        }
        return $paymentToken;
    }

    /**
     * Get payment token by ID
     *
     * @param mixed $paymenttokenId
     * @return PaymentTokenInterface
     * @throws NoSuchEntityException
     */
    public function get($paymenttokenId): PaymentTokenInterface
    {
        $paymentToken = $this->paymentTokenFactory->create();
        /** @var \Magento\Framework\Model\AbstractModel $paymentTokenModel */
        $paymentTokenModel = $paymentToken;
        $this->resource->load($paymentTokenModel, $paymenttokenId);
        
        // Use getPaymenttokenId() instead of getId() to match your interface
        if (!$paymentToken->getPaymenttokenId()) {
            throw new NoSuchEntityException(__('PaymentToken with id "%1" does not exist.', $paymenttokenId));
        }
        return $paymentToken;
    }

    /**
     * Get list of payment tokens
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return PaymentTokenSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria): PaymentTokenSearchResultsInterface
    {
        $collection = $this->paymentTokenCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete payment token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PaymentTokenInterface $paymentToken): bool
    {
        try {
            $paymentTokenModel = $this->paymentTokenFactory->create();
            /** @var \Magento\Framework\Model\AbstractModel $paymentTokenModelForLoad */
            $paymentTokenModelForLoad = $paymentTokenModel;
            $this->resource->load($paymentTokenModelForLoad, $paymentToken->getPaymenttokenId());
            
            /** @var \Magento\Framework\Model\AbstractModel $paymentTokenModelForDelete */
            $paymentTokenModelForDelete = $paymentTokenModel;
            $this->resource->delete($paymentTokenModelForDelete);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PaymentToken: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Delete payment token by ID
     *
     * @param mixed $paymenttokenId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($paymenttokenId): bool
    {
        return $this->delete($this->get($paymenttokenId));
    }
}

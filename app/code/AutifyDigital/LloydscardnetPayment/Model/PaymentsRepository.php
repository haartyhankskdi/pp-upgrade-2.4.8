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

use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterface;
use AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterfaceFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments as ResourcePayments;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class PaymentsRepository implements PaymentsRepositoryInterface
{
    /**
     * @var ResourcePayments
     */
    protected $resource;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsInterfaceFactory
     */
    protected $paymentsFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $paymentsModelFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var PaymentsSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @param ResourcePayments $resource
     * @param PaymentsInterfaceFactory $paymentsFactory
     * @param PaymentsFactory $paymentsModelFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param PaymentsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourcePayments $resource,
        PaymentsInterfaceFactory $paymentsFactory,
        PaymentsFactory $paymentsModelFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        PaymentsSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->paymentsFactory = $paymentsFactory;
        $this->paymentsModelFactory = $paymentsModelFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Function for save data
     *
     * @param PaymentsInterface $payments
     * @return PaymentsInterface
     * @throws CouldNotSaveException
     */
    public function save(PaymentsInterface $payments): PaymentsInterface
    {
        try {
            // If the $payments object is already a model instance, just save it directly
            if ($payments instanceof \AutifyDigital\LloydscardnetPayment\Model\Payments) {
                $this->resource->save($payments);
                return $payments;
            }
            
            /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $model */
            $model = $this->paymentsModelFactory->create();

            if ($payments->getPaymentsId()) {
                $this->resource->load($model, $payments->getPaymentsId());
            }

            $model->setPaymentsId($payments->getPaymentsId());
            $model->setAmount($payments->getAmount());
            $model->setStatus($payments->getStatus());
            $model->setOrderId($payments->getOrderId());
            $model->setOrderIncrementId($payments->getOrderIncrementId());
            $model->setRemoteReference($payments->getRemoteReference());
            $model->setRemoteMessage($payments->getRemoteMessage());
            $model->setRemoteStatusOrCode($payments->getRemoteStatusOrCode());
            $model->setCardnetOrderId($payments->getCardnetOrderId());
            $model->setRedirectEmailSent($payments->getRedirectEmailSent());
            $model->setLast4($payments->getLast4());
            $model->setBrand($payments->getBrand());
            $model->setIpgTransactionId($payments->getIpgTransactionId());
            $model->setIframeReceived($payments->getIframeReceived());
            $model->setThreedsReceived($payments->getThreedsReceived());

            $this->resource->save($model);

            return $model;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the payments: %1',
                $exception->getMessage()
            ));
        }
        return $payments;
    }

    /**
     * Function for get data
     *
     * @param string $paymentsId
     * @return PaymentsInterface
     * @throws NoSuchEntityException
     */
    public function get($paymentsId): PaymentsInterface
    {
        $paymentsModel = $this->paymentsModelFactory->create();
        $this->resource->load($paymentsModel, $paymentsId);
        if (!$paymentsModel->getId()) {
            throw new NoSuchEntityException(__('Payments with id "%1" does not exist.', $paymentsId));
        }
        return $paymentsModel;
    }

    /**
     * Function for get list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \AutifyDigital\LloydscardnetPayment\Api\Data\PaymentsSearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): PaymentsSearchResultsInterface {
        $collection = $this->paymentsCollectionFactory->create();

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
     * Function for delete data
     *
     * @param PaymentsInterface $payments
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PaymentsInterface $payments): bool
    {
        try {
            if ($payments instanceof \AutifyDigital\LloydscardnetPayment\Model\Payments) {
                $this->resource->delete($payments);
            } else {
                $paymentsModel = $this->paymentsModelFactory->create();
                $this->resource->load($paymentsModel, $payments->getPaymentsId());
                $this->resource->delete($paymentsModel);
            }
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Payments: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * Function for delete data by id
     *
     * @param string $paymentsId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($paymentsId): bool
    {
        return $this->delete($this->get($paymentsId));
    }
}

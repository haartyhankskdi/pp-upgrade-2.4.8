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

namespace AutifyDigital\LloydscardnetPayment\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class SavedCards implements SectionSourceInterface
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * SavedCards constructor.
     *
     * @param Session $customerSession
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $urlBuilder
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Session $customerSession,
        CollectionFactory $collectionFactory,
        UrlInterface $urlBuilder,
        EncryptorInterface $encryptor
    ) {
        $this->customerSession = $customerSession;
        $this->collectionFactory = $collectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->encryptor = $encryptor;
    }

    /**
     * Get saved cards data for the current customer
     *
     * @return array
     */
    public function getSectionData()
    {
        $customerId = $this->customerSession->getCustomerId();
        
        if (!$customerId) {
            return ['cards' => []];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', ['eq' => (string)$customerId]);
        
        $cards = [];
        foreach ($collection->getItems() as $card) {
            $cards[] = [
                'id' => $this->encryptor->encrypt((string)$card->getPaymenttokenId()),
                'masked' => $card->getMasked(),
                'brand' => ucfirst($card->getBrand()),
                'last4' => $card->getLast4(),
                'exp_month' => $card->getExpMonth(),
                'exp_year' => $card->getExpYear(),
                'delete_url' => $this->urlBuilder->getUrl(
                    'lloyds/paymentjs/delete',
                    ['id' => $this->encryptor->encrypt((string)$card->getPaymenttokenId())]
                )
            ];
        }

        return ['cards' => $cards];
    }
}

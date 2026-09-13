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

namespace AutifyDigital\LloydscardnetPayment\Block;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Session;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class SavedCards
 * Responsible for retrieving saved cards of the customer and providing
 * a delete URL for each card.
 */
class SavedCards extends Template
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * SavedCards constructor.
     *
     * @param Template\Context $context
     * @param Session $customerSession
     * @param CollectionFactory $collectionFactory
     * @param EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Session $customerSession,
        CollectionFactory $collectionFactory,
        EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->collectionFactory = $collectionFactory;
        $this->encryptor = $encryptor;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve saved cards for the current customer.
     *
     * @return \Magento\Framework\DataObject[]
     */
    public function getSavedCards(): array
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', ['eq' => (string)$customerId]);
        return $collection->getItems();
    }

    /**
     * Generate the delete URL for a specific saved card.
     *
     * @param int $tokenId
     * @return string
     */
    public function getDeleteUrl(int $tokenId): string
    {
        return $this->getUrl('lloyds/paymentjs/delete', ['id' => $this->encryptor->encrypt((string)$tokenId)]);
    }
}

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

namespace AutifyDigital\LloydscardnetPayment\Controller\Paymentjs;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use AutifyDigital\LloydscardnetPayment\Model\PaymentTokenFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentTokenRepositoryInterface;

class Delete extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param Session $customerSession
     * @param EncryptorInterface $encryptor
     * @param JsonFactory $resultJsonFactory
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        Context $context,
        PaymentTokenFactory $paymentTokenFactory,
        Session $customerSession,
        EncryptorInterface $encryptor,
        JsonFactory $resultJsonFactory,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->customerSession = $customerSession;
        $this->encryptor = $encryptor;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->customerSession->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', 'true');
        }
        return parent::dispatch($request);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $cardId = $this->encryptor->decrypt($this->getRequest()->getParam('id'));
            $customerId = $this->customerSession->getCustomerId();
            $card = $this->paymentTokenRepository->get((int) $cardId);

            if ($card->getCustomerId() == $customerId) {
                $this->paymentTokenRepository->delete($card);

                return $resultJson->setData([
                    'success' => true,
                    'message' => __('The card has been deleted.')
                ]);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('You are not authorized to delete this card.')
                ]);
            }
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while deleting the card.')
            ]);
        }
    }
}

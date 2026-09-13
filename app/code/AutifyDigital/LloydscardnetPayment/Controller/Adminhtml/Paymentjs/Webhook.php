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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Paymentjs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\AdminOrder\Create as OrderCreate;
use Magento\Backend\Model\Session\Quote as AdminQuoteSession;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Class PaymentJS Webhook for Admin Order Creation
 */
class Webhook extends Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::create';

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var Data
     */
    protected $autifyDigitalHelper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var OrderCreate
     */
    protected $orderCreateModel;

    /**
     * @var AdminQuoteSession
     */
    protected $adminQuoteSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Webhook constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param Data $autifyDigitalHelper
     * @param JsonHelper $jsonHelper
     * @param SerializerInterface $serializer
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderCreate $orderCreateModel
     * @param AdminQuoteSession $adminQuoteSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        Data $autifyDigitalHelper,
        JsonHelper $jsonHelper,
        SerializerInterface $serializer,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderCreate $orderCreateModel,
        AdminQuoteSession $adminQuoteSession,
        CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->autifyDigitalHelper = $autifyDigitalHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderCreateModel = $orderCreateModel;
        $this->adminQuoteSession = $adminQuoteSession;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Create csrf validation exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate for csrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->jsonResultFactory->create();
        $this->autifyDigitalHelper->addLog("Admin Webhook Call");

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $rawContent = $request->getContent();

        if (isset($_SERVER['HTTP_CLIENT_TOKEN'])) { // phpcs:ignore
            $clientToken = $_SERVER['HTTP_CLIENT_TOKEN']; // phpcs:ignore
        } else {
            $clientToken = null;
            $this->autifyDigitalHelper->addLog('Unable to fetch Client-Token');
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
            $result->setData(['error_message' => __('Error While fetching the Client Token.')]);
            return $result;
        }

        $data = [];
        if ($rawContent) {
            $data = (array) $this->jsonHelper->jsonDecode($rawContent);
        }

        if (empty($data)) {
            $this->autifyDigitalHelper->addLog('HTTP Code: 403');
            $this->autifyDigitalHelper->addLog('Required Parameters Not Provided');
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);
            $result->setData(['error_message' => __('Error While fetching the data.')]);
            return $result;
        }

        $this->autifyDigitalHelper->addLog('--------- Start Admin Payload Data ----------------');
        $this->autifyDigitalHelper->addLog($this->autifyDigitalHelper->getJsonEncode($data));
        $this->autifyDigitalHelper->addLog('--------- End Admin Payload Data ----------------');

        if (isset($data['error']) && $data['error'] == false && isset($data['card']['token'])) {
            $this->autifyDigitalHelper->addLog('Admin Webhook: paymentToken found');
            $gatewayToken = $data['card']['token'];
            $tokenData = [
                'token' => $data['card']['token'],
                'masked' => $data['card']['masked'],
                'brand' => $data['card']['brand'],
                'last4' => $data['card']['last4'],
                'exp_month' => $data['card']['exp']['month'],
                'exp_year' => $data['card']['exp']['year'],
            ];

            try {
                $this->savePaymentDetails($gatewayToken, $tokenData, $clientToken);
                $this->updateAdminQuotePayment($gatewayToken, $tokenData);

                $result->setData(['success' => true, 'message' => __('Payment information saved successfully.')]);
            } catch (\Exception $e) {
                $this->autifyDigitalHelper->addLog('Admin Webhook Exception: ' . $e->getMessage());
                $result->setData(
                    [
                        'success' => false,
                        'message' => __('Error processing payment: %1', $e->getMessage())
                    ]
                );
            }
        } else {
            if (isset($data['reason'])) {
                $this->autifyDigitalHelper->addLog('Admin Payment JS token Error: ' . $data['reason']);
                $result->setData(['success' => false, 'message' => __('Payment error: %1', $data['reason'])]);
            } else {
                $result->setData(['success' => false, 'message' => __('Unknown payment error occurred.')]);
            }
        }

        return $result;
    }

    /**
     * Save the Payment Data and Token in the vault_payment_token table
     *
     * @param string $gatewayToken
     * @param array $paymentDetails
     * @param string $clientToken
     * @return void
     */
    private function savePaymentDetails($gatewayToken, $paymentDetails, $clientToken): void
    {
        $paymentDetailsArr['paymentjs_token'] = $paymentDetails;
        $paymentTokens = $this->paymentTokenRepository->getList($this->searchCriteriaBuilder->create());

        foreach ($paymentTokens->getItems() as $paymentToken) {
            $details = $paymentToken->getTokenDetails();
            $detailsArray = $this->serializer->unserialize($details);

            if (isset($detailsArray['client_token']) && $detailsArray['client_token'] === $clientToken) {
                $paymentToken->setGatewayToken($gatewayToken);

                $paymentToken->setPublicHash(hash('sha256', $gatewayToken));

                $updatedDetails = array_merge($detailsArray, $paymentDetailsArr); // phpcs:ignore
                $vaultDetail = $this->serializer->serialize($updatedDetails);
                $paymentToken->setTokenDetails($vaultDetail);

                $this->paymentTokenRepository->save($paymentToken);
                $this->autifyDigitalHelper->addLog('Admin Payment token saved successfully');
                break;
            }
        }
    }

    /**
     * Update current admin quote payment information
     *
     * @param string $gatewayToken
     * @param array $tokenData
     * @return void
     */
    private function updateAdminQuotePayment($gatewayToken, $tokenData): void
    {
        try {
            $quote = $this->adminQuoteSession->getQuote();

            if ($quote->getId()) {
                $payment = $quote->getPayment();
                $payment->setAdditionalInformation('gateway_token', $gatewayToken);
                $payment->setAdditionalInformation('card_details', $this->serializer->serialize($tokenData));

                if (isset($tokenData['last4'])) {
                    $payment->setCcLast4($tokenData['last4']);
                }
                
                if (isset($tokenData['brand'])) {
                    $payment->setAdditionalInformation('cc_type', $tokenData['brand']);
                }

                if (isset($tokenData['exp_month']) && isset($tokenData['exp_year'])) {
                    $payment->setAdditionalInformation('cc_exp_month', $tokenData['exp_month']);
                    $payment->setAdditionalInformation('cc_exp_year', $tokenData['exp_year']);
                }

                $this->quoteRepository->save($quote);
                $this->autifyDigitalHelper->addLog('Admin quote payment data updated successfully');
            } else {
                $this->autifyDigitalHelper->addLog('No active admin quote found to update payment');
            }
        } catch (\Exception $e) {
            $this->autifyDigitalHelper->addLog('Error updating admin quote payment: ' . $e->getMessage());
            throw $e;
        }
    }
}

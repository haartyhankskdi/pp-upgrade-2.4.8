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

/**
 * Class PaymentJS Webhook
 */
class Webhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $autifyDigitalHelper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
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
     * Webhook constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param Data $autifyDigitalHelper
     * @param JsonHelper $jsonHelper
     * @param SerializerInterface $serializer
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        Data $autifyDigitalHelper,
        JsonHelper $jsonHelper,
        SerializerInterface $serializer,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->autifyDigitalHelper = $autifyDigitalHelper;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->jsonResultFactory->create();
        $this->autifyDigitalHelper->addLog("Webhook Call");

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        $rawContent = $request->getContent();
        $clientToken = $request->getHeader('client-token') ?? $request->getHeader('Client-Token');
        if (!$clientToken) {
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

        $this->autifyDigitalHelper->addLog('--------- Start Payload Data ----------------');
        $this->autifyDigitalHelper->addLog($data, true);
        $this->autifyDigitalHelper->addLog('--------- End Payload Data ----------------');

        if (isset($data['error']) && $data['error'] == false && isset($data['card']['token'])) {
            $this->autifyDigitalHelper->addLog('Webhook: paymentToken founddd');
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
            } catch (\Exception $e) {
                $this->autifyDigitalHelper->addLog('Webhook: Exception: ' . $e->getMessage());
            }
        } else {
            if (isset($data['reason'])) {
                $this->autifyDigitalHelper->addLog('Payment JS token Error : ' . $data['reason']);
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
                break;
            }
        }
    }
}

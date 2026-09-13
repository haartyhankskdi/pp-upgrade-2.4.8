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

namespace AutifyDigital\LloydscardnetPayment\Controller\Wallet;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class GooglePayCreateOrder extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var SerializerInterface
     */
    protected $jsonSerializer;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteManagement $quoteManagement
     * @param SerializerInterface $jsonSerializer
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CartRepositoryInterface $quoteRepository,
        QuoteManagement $quoteManagement,
        SerializerInterface $jsonSerializer,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->jsonSerializer = $jsonSerializer;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute Googlepay Create Order
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $params = $this->jsonSerializer->unserialize($request->getContent());

        if (isset($params['billingContact']) && isset($params['shippingContact']) && is_array($params['shippingContact'])) {
            try {
                // Get current session quote (works for guest and logged-in)
                $quote = $this->checkoutSession->getQuote();

                if (!$quote->getId()) {
                    throw new LocalizedException(__("No active quote found."));
                }

                // Set customer email for guest
                if (!$this->customerSession->isLoggedIn()) {
                    $quote->setCustomerEmail($params['billingContact']['emailAddress'] ?? 'guest@example.com');
                }

                // Set addresses from Apple Pay contact info
                $billingAddress = $this->convertContact($params['billingContact']);
                $shippingAddress = $this->convertContact($params['shippingContact']);

                $quote->getBillingAddress()->addData($billingAddress);
                $quote->getShippingAddress()->addData($shippingAddress);

                $quote->getShippingAddress()
                    ->setCollectShippingRates(false)
                    ->collectShippingRates()
                    ->setShippingMethod($params['shipping_method']);

                $quote->setPaymentMethod($params['payment_method']);
                $quote->getPayment()->importData(['method' => $params['payment_method']]);

                $quote->collectTotals();
                $this->quoteRepository->save($quote);

                $order = $this->quoteManagement->submit($quote);

                if ($order) {
                    $this->checkoutSession->setLastQuoteId($quote->getId());
                    $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
                    $this->checkoutSession->setLastOrderId($order->getId());
                    $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                    $this->checkoutSession->setLastOrderStatus($order->getStatus());
                }

                return $result->setData([
                    'success' => true,
                    'order_id' => $order->getId(),
                    'increment_id' => $order->getIncrementId()
                ]);
            } catch (\Exception $e) {
                return $result->setData([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            return $result->setData([
                'success' => false,
                'message' => 'Order creation failed'
            ]);
        }
    }

    /**
     * Convert Contact
     *
     * @param array $contact
     * @return array
     */
    private function convertContact($contact)
    {
        $nameParts = explode(' ', $contact['name'], 2);
        $firstname = $nameParts[0] ?? 'Guest';
        $lastname = $nameParts[1] ?? 'User';
        $address1 = $contact['address1'] ?? '';
        $address2 = $contact['address2'] ?? '';
        $address3 = $contact['address3'] ?? '';

        $firstnameLimit = (int) $this->scopeConfig->getValue(
            'payment/cardnetgooglepay/firstname_limit',
            ScopeInterface::SCOPE_STORE
        );
        $lastnameLimit = (int) $this->scopeConfig->getValue(
            'payment/cardnetgooglepay/lastname_limit',
            ScopeInterface::SCOPE_STORE
        );
        $address1Limit = (int) $this->scopeConfig->getValue(
            'payment/cardnetgooglepay/address1_limit',
            ScopeInterface::SCOPE_STORE
        );
        $address2Limit = (int) $this->scopeConfig->getValue(
            'payment/cardnetgooglepay/address2_limit',
            ScopeInterface::SCOPE_STORE
        );

        if ($firstnameLimit > 0) {
            $firstname = mb_substr($firstname, 0, $firstnameLimit);
        }
        if ($lastnameLimit > 0) {
            $lastname = mb_substr($lastname, 0, $lastnameLimit);
        }
        if ($address1Limit > 0 && $address1 !== '') {
            $address1 = mb_substr($address1, 0, $address1Limit);
        }
        if ($address2Limit > 0 && $address2 !== '') {
            $address2 = mb_substr($address2, 0, $address2Limit);
        }

        return [
            'firstname'   => $firstname,
            'lastname'    => $lastname,
            'street'      => array_filter([
                $address1,
                $address2,
                $address3
            ]),
            'city'        => $contact['locality'] ?? '',
            'region'      => $contact['administrativeArea'] ?? '',
            'postcode'    => $contact['postalCode'] ?? '',
            'country_id'  => $contact['countryCode'] ?? 'GB',
            'telephone'   => $contact['phoneNumber'] ?? '0000000000',
            'email'       => $contact['email'] ?? 'guest@example.com'
        ];
    }
}

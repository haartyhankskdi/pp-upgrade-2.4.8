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
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\ShippingMethodManagement;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class ShippingOptions extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ShippingMethodManagement
     */
    protected $shippingMethodManagement;

    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Cart $cart
     * @param QuoteFactory $quoteFactory
     * @param ShippingMethodManagement $shippingMethodManagement
     * @param AddressFactory $addressFactory
     * @param JsonFactory $resultJsonFactory
     * @param SerializerInterface $serializer
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context $context,
        Cart $cart,
        QuoteFactory $quoteFactory,
        ShippingMethodManagement $shippingMethodManagement,
        AddressFactory $addressFactory,
        JsonFactory $resultJsonFactory,
        SerializerInterface $serializer,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->cart = $cart;
        $this->quoteFactory = $quoteFactory;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->addressFactory = $addressFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Return Shipping Options
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();
        $addressData = $this->serializer->unserialize($request->getContent());

        try {
            $quote = $this->cart->getQuote();

            if (!$quote->getId() || !$addressData || !isset($addressData['address'])) {
                return $resultJson->setData([]);
            }
            $address = $addressData['address'];

            $country = $address['country_id']; // 'US'
            $region = $address['region']; // 'California'
            $postcode = $address['postcode'];

            // Set a dummy shipping address for estimation
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCountryId($country)
                ->setPostcode($postcode)
                ->setRegion($region)
                ->setCollectShippingRates(true);

            $quote->collectTotals();
            $this->cartRepository->save($quote);

            $excludedCarriers = ['instore', 'instorepickup', 'in_store_pickup'];

            $rates = $shippingAddress->getGroupedAllShippingRates();
            $methods = [];

            foreach ($rates as $carrierCode => $carrierRates) {
                if (in_array($carrierCode, $excludedCarriers)) {
                    continue;
                }
                foreach ($carrierRates as $rate) {
                    $methods[] = [
                        'carrier_code'    => $carrierCode,
                        'method_code'     => $rate->getMethod(),
                        'carrier_title'   => $rate->getCarrierTitle(),
                        'method_title'    => $rate->getMethodTitle(),
                        'amount'          => $rate->getPrice(),
                        'base_amount'     => $rate->getBasePrice(),
                        'available'       => true,
                        'error_message'   => '',
                        'price_excl_tax'  => $rate->getPriceExclTax(),
                        'price_incl_tax'  => $rate->getPriceInclTax()
                    ];
                }
            }

            return $resultJson->setData($methods);
        } catch (\Exception $e) {
            return $resultJson->setData([]);
        }
    }
}

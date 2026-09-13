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
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;

class ShippingUpdate extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var ShippingMethodManagementInterface
     */
    protected $shippingMethodManagement;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Cart $cart
     * @param QuoteRepository $quoteRepository
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param Json $serializer
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Cart $cart,
        QuoteRepository $quoteRepository,
        ShippingMethodManagementInterface $shippingMethodManagement,
        Json $serializer
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cart = $cart;
        $this->quoteRepository = $quoteRepository;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Update Shippings
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $quote = $this->cart->getQuote();
            /** @var \Magento\Framework\App\Request\Http $request */
            $request = $this->getRequest();
            $data = $this->serializer->unserialize($request->getContent());

            if (!$quote->getId() || !$data || !isset($data['addressInformation'])) {
                return $resultJson->setData([]);
            }

            $address = $data['addressInformation']['address'];
            $methodCode = $data['addressInformation']['shipping_method_code'] ?? '';
            $carrierCode = $data['addressInformation']['shipping_carrier_code'] ?? '';

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->addData($address)
                ->setCollectShippingRates(false)
                ->collectShippingRates()
                ->setShippingMethod($carrierCode . '_' . $methodCode);

            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            $totals = [
                'grand_total' => (float)$quote->getGrandTotal(),
                'base_grand_total' => (float)$quote->getBaseGrandTotal(),
                'subtotal' => (float)$quote->getSubtotal(),
                'base_subtotal' => (float)$quote->getBaseSubtotal(),
                'discount_amount' => (float)$shippingAddress->getDiscountAmount(),
                'base_discount_amount' => (float)$shippingAddress->getBaseDiscountAmount(),
                'subtotal_with_discount' => (float)$shippingAddress->getSubtotalWithDiscount(),
                'base_subtotal_with_discount' => (float)$shippingAddress->getBaseSubtotalWithDiscount(),
                'shipping_amount' => (float)$shippingAddress->getShippingAmount(),
                'base_shipping_amount' => (float)$shippingAddress->getBaseShippingAmount(),
                'shipping_discount_amount' => (float)$shippingAddress->getShippingDiscountAmount(),
                'base_shipping_discount_amount' => (float)$shippingAddress->getBaseShippingDiscountAmount(),
                'tax_amount' => (float)$shippingAddress->getTaxAmount(),
                'base_tax_amount' => (float)$shippingAddress->getBaseTaxAmount(),
                'weee_tax_applied_amount' => $shippingAddress->getData('weee_tax_applied_amount'),
                'shipping_tax_amount' => (float)$shippingAddress->getShippingTaxAmount(),
                'base_shipping_tax_amount' => (float)$shippingAddress->getBaseShippingTaxAmount(),
                'subtotal_incl_tax' => (float)$shippingAddress->getSubtotalInclTax(),
                'base_subtotal_incl_tax' => $shippingAddress->getData('base_subtotal_incl_tax'),
                'shipping_incl_tax' => (float)$shippingAddress->getShippingInclTax(),
                'base_shipping_incl_tax' => (float)$shippingAddress->getBaseShippingInclTax(),
                'base_currency_code' => $quote->getBaseCurrencyCode(),
                'quote_currency_code' => $quote->getQuoteCurrencyCode(),
                'coupon_code' => $quote->getCouponCode(),
                'items_qty' => (int)$quote->getItemsQty()
            ];

            return $resultJson->setData(
                $totals
            );
        } catch (\Exception $e) {
            return $resultJson->setData([
            ]);
        }
    }
}

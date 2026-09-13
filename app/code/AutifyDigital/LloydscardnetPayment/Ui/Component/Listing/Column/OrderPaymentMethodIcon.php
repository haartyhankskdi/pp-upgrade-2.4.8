<?php

/**
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;

class OrderPaymentMethodIcon extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory
     */
    protected $lcPaymentsFactory;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     * @param LcPaymentsFactory $lcPaymentsFactory
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore();
            $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            foreach ($dataSource['data']['items'] as &$item) {
                // payment_method and brand are joined into the grid collection
                // (see Model\ResourceModel\Order\Grid\Collection), so no per-row
                // order load or payments query is required here.
                $paymentMethod = $this->getOrderPaymentMethod($item);
                $brand = $item['brand'] ?? '';

                $iconHtml = $this->getPaymentMethodIcon($paymentMethod, $brand, $mediaUrl);
                $item[$this->getData('name')] = $iconHtml;
            }
        }

        return $dataSource;
    }

    /**
     * Get payment method from order
     *
     * @param array $item
     * @return string
     */
    private function getOrderPaymentMethod($item)
    {
        if (isset($item['payment_method'])) {
            return $item['payment_method'];
        }

        try {
            if (isset($item['entity_id'])) {
                $order = $this->orderRepository->get($item['entity_id']);
                $payment = $order->getPayment();
                return $payment->getMethod();
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }

    /**
     * Get payment method icon HTML
     *
     * @param string $paymentMethod
     * @param string $brand
     * @param string $mediaUrl
     * @return string
     */
    private function getPaymentMethodIcon($paymentMethod, $brand, $mediaUrl)
    {
        $iconUrl = '';
        $altText = '';
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC);

        switch (strtolower($paymentMethod)) {
            case 'lcnetredirect':
            case 'lbopcheckoutsolution':
                $iconUrl = $mediaUrl . 'lloydscardnet/default/lloyds_logo.png';
                $altText = 'lcnet redirect';
                break;
            case 'cardnetgooglepay':
                $iconUrl = $mediaUrl . 'lloydscardnet/default/GPay.png';
                $altText = 'Google Pay';
                break;
            case 'cardnetapplepay':
            case 'applepay':
                $iconUrl = $mediaUrl . 'lloydscardnet/default/ApplePay.png';
                $altText = 'Apple Pay';
                break;
            case 'lcnetpaymentjs':
                if (!empty($brand) && $brand != "N/A") {
                    $iconUrl = $this->getMagentoBrandIcon($brand, $baseUrl);
                    $altText = ucfirst($brand);
                }
                break;
            default:
                if (!empty($brand) && $brand != "N/A") {
                    $iconUrl = $this->getMagentoBrandIcon($brand, $baseUrl);
                    $altText = ucfirst($brand);
                } else {
                    $iconUrl = '';
                    $altText = $paymentMethod;
                }
                break;
        }

        if (!empty($iconUrl)) {
            return sprintf(
                '<img src="%s" alt="%s" title="%s" style="width: 32px; height: 20px; object-fit: contain;" />',
                $iconUrl,
                $altText,
                $altText
            );
        }

        return $altText;
    }

    /**
     * Get Magento's built-in brand icon URL
     *
     * @param string $brand
     * @param string $baseUrl
     * @return string
     */
    private function getMagentoBrandIcon($brand, $baseUrl)
    {
        $brandLower = strtolower($brand);
        
        $brandIcons = [
            'vi' => 'vi.png',
            'visa' => 'vi.png',
            'mc' => 'mc.png',
            'mastercard' => 'mc.png',
            'ae' => 'ae.png',
            'amex' => 'ae.png',
            'american express' => 'ae.png',
            'di' => 'di.png',
            'discover' => 'di.png',
            'jcb' => 'jcb.png',
            'dn' => 'dn.png',
            'diners' => 'dn.png',
            'maestro' => 'sm.png',
            'sm' => 'sm.png',
            'unionpay' => 'un.png',
            'un' => 'un.png',
        ];

        $iconFile = $brandIcons[$brandLower] ?? '';
        
        if (empty($iconFile)) {
            return '';
        }
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $locale = $store->getConfig('general/locale/code');
        return $baseUrl . 'frontend/Magento/blank/' . $locale . '/Magento_Payment/images/cc/' . $iconFile;
    }
}

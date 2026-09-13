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

class PaymentMethodIcon extends Column
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
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
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
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        if (isset($dataSource['data']['items'])) {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore();
            $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            
            foreach ($dataSource['data']['items'] as &$item) {
                $paymentMethod = $item['payment_method'] ?? '';
                $brand = $item['brand'] ?? '';
                
                $iconHtml = $this->getPaymentMethodIcon($paymentMethod, $brand, $mediaUrl);
                $item[$this->getData('name')] = $iconHtml;
            }
        }

        return $dataSource;
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
                $iconUrl = $mediaUrl . 'lloydscardnet/default/lloyds_logo.png';
                $altText = 'lcnet redirect';
                break;
            case 'cardnetgooglepay':
                $iconUrl = $mediaUrl . 'lloydscardnet/default/GPay.png';
                $altText = 'Google Pay';
                break;
            case 'cardnetapplepay':
                $iconUrl = $mediaUrl . 'lloydscardnet/default/ApplePay.png';
                $altText = 'Apple Pay';
                break;
            case 'lcnetpaymentjs':
                if (!empty($brand) && $brand != "N/A") {
                    $iconUrl = $this->getMagentoBrandIcon($brand, $baseUrl);
                    $altText = ucfirst($brand);
                } else {
                    $iconUrl = $mediaUrl . 'lloydscardnet/default/lloyds_logo.png';
                    $altText = 'Credit Card';
                }
                break;
            default:
                $iconUrl = $mediaUrl . 'lloydscardnet/default/lloyds_logo.png';
                $altText = 'Payment';
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

        $brandLower = strtolower($brand);
        $iconFile = $brandIcons[$brandLower] ?? 'generic.png';
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        
        $locale = $store->getConfig('general/locale/code');
        return $baseUrl . 'frontend/Magento/blank/' . $locale . '/Magento_Payment/images/cc/' . $iconFile;
    }
}

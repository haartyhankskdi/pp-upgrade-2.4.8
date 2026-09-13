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

namespace AutifyDigital\LloydscardnetPayment\Model;

use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use AutifyDigital\LloydscardnetPayment\Helper;
use Magento\Customer\Model\Session as CustomerSession;
use AutifyDigital\LloydscardnetPayment\Model\Config;

/**
 * Class ConfigProvider
 * Config provider for the payment method
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = "lcnetpaymentjs";

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Config
     *
     * @var \AutifyDigital\LloydscardnetPayment\Model\Config
     */
    protected $autifyConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param UrlInterface $urlInterface
     * @param CheckoutSession $checkoutSession
     * @param Helper\Data $helper
     * @param CustomerSession $customerSession
     * @param Config $autifyConfig
     */
    public function __construct(
        UrlInterface $urlInterface,
        CheckoutSession $checkoutSession,
        Helper\Data $helper,
        CustomerSession $customerSession,
        \AutifyDigital\LloydscardnetPayment\Model\Config $autifyConfig
    ) {
        $this->urlBuilder = $urlInterface;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->autifyConfig = $autifyConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    #[\Override]
    public function getConfig()
    {

        $mainLogoPath = $this->autifyConfig->getMediaUrl() . "lloydscardnet/";
        $redirectPaymentLogo = $this->autifyConfig->getConfig('payment/lcnetredirect/payment_logo') ?
        $mainLogoPath . $this->autifyConfig->getConfig('payment/lcnetredirect/payment_logo') : '';

        $directPaymentLogo = $this->autifyConfig->getConfig('payment/lcnetpaymentjs/payment_logo') ?
        $mainLogoPath  . $this->autifyConfig->getConfig('payment/lcnetpaymentjs/payment_logo') : '';

        $applePayPaymentLogo = $this->autifyConfig->getConfig('payment/cardnetapplepay/payment_logo') ?
        $mainLogoPath . $this->autifyConfig->getConfig('payment/cardnetapplepay/payment_logo') : '';

        $googlePayPaymentLogo = $this->autifyConfig->getConfig('payment/cardnetgooglepay/payment_logo') ?
        $mainLogoPath . $this->autifyConfig->getConfig('payment/cardnetgooglepay/payment_logo') : '';

        $paymentMode = $this->autifyConfig->getConfig('payment/basic/lloyds_mode') == 'Test' ?
        'TEST' : 'LIVE';

        return [
            'payment' => [
                self::CODE => [
                    'payment_code' => self::CODE,
                    'saveCards' => $this->getSavedCards(),
                    'placeholder_card' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_card'),
                    'placeholder_cvv' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_cvv'),
                    'placeholder_name' => $this->helper->getConfig('payment/lcnetpaymentjs/placeholder_name'),
                    'custom_css' => $this->helper->getConfig('payment/lcnetpaymentjs/css_code'),
                    'payment_logo' => $directPaymentLogo,
                    'allowed_brands' => $this->getPaymentJsAllowedBrands(),
                    'payment_action' => $this->helper->getConfig('payment/lcnetpaymentjs/payment_action'),
                ],
                'lcnetredirect' => [
                    'payment_logo' => $redirectPaymentLogo,
                    'payment_tooltip' => $this->autifyConfig->getConfig('payment/lcnetredirect/tooltip_before_button'),
                    'saveCards' => $this->getSavedCards()
                ],
                'cardnetapplepay' => [
                    'payment_logo' => $applePayPaymentLogo,
                    'button_color' => $this->autifyConfig->getConfig('payment/cardnetapplepay/button_color'),
                    'supported_networks' =>
                    $this->autifyConfig->getConfig('payment/cardnetapplepay/supported_networks'),
                    'payment_mode' => $paymentMode,
                    'merchantIdentifier' =>
                    $this->autifyConfig->getConfig('payment/cardnetapplepay/merchant_identifier'),
                    'minOrderAmount' => $this->autifyConfig->getConfig('payment/cardnetapplepay/min_order_total'),
                    'maxOrderAmount' => $this->autifyConfig->getConfig('payment/cardnetapplepay/max_order_total'),
                    'payment_integration_type' => $this->helper->getApplepayPaymentMethodInteration(),
                    'isBillingAddressRequired' => true
                ],
                'cardnetgooglepay' => [
                    'payment_logo' => $googlePayPaymentLogo,
                    'currency'     => $this->helper->getOrderSession()->getQuote()->getQuoteCurrencyCode(),
                    'payment_mode' => $paymentMode == 'TEST' ? 'TEST' : 'PRODUCTION',
                    'button_color' => $this->autifyConfig->getConfig('payment/cardnetgooglepay/button_color'),
                    'merchant_name' => $this->autifyConfig->getConfig('payment/cardnetgooglepay/merchant_name'),
                    'merchant_id' => $this->autifyConfig->getConfig('payment/cardnetgooglepay/merchant_id'),
                    'gateway_merchant_id' => $this->autifyConfig->getGatewayStoreId(),
                    'supported_networks' =>
                    $this->autifyConfig->getConfig('payment/cardnetgooglepay/supported_networks'),
                    'minOrderAmount' => $this->autifyConfig->getConfig('payment/cardnetgooglepay/min_order_total'),
                    'maxOrderAmount' => $this->autifyConfig->getConfig('payment/cardnetgooglepay/max_order_total'),
                    'payment_integration_type' => $this->helper->getGooglepayPaymentMethodInteration(),
                    'isBillingAddressRequired' => true
                ],
                'lbopcheckoutsolution' => [
                    'payment_code' => 'lbopcheckoutsolution',
                    'title' => $this->autifyConfig->getConfig('payment/lbopcheckoutsolution/title'),
                    'saveCards' => $this->getSavedCards(1)
                ]
            ]
        ];
    }

    /**
     * Get allowed card brands for PaymentJS
     *
     * @return string[]
     */
    public function getPaymentJsAllowedBrands(): array
    {
        $configured = (string) $this->autifyConfig->getConfig('payment/lcnetpaymentjs/supported_networks');

        if ($configured === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }

    /**
     * Get all saved cards for the logged in customer
     *
     * @param int $isFiserv
     * @return array
     */
    public function getSavedCards(int $isFiserv = 0)
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $cardArr = $this->helper->getPaymentTokensByCustomerId($customerId, $isFiserv);
            if ($cardArr) {
                return $cardArr;
            }
        }
        return [];
    }
}

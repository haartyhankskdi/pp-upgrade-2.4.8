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

namespace AutifyDigital\LloydscardnetPayment\Block\Applepay;

use Magento\Catalog\Block\Product\View as ProductView;
use Magento\Catalog\Block\Product\Context;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\Json\EncoderInterface as JsonEncoder;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Product extends ProductView
{
    /**
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $stringUtils
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Data $helper
     * @param array $data
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $stringUtils,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * Return Helper
     *
     * @return \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }
}

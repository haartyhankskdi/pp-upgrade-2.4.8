<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Mageplaza\GoogleTagManager\Model\Resolver\Get;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class GtmAddOrRemoveCart
 * @package Mageplaza\GoogleTagManager\Model\Resolver\Get
 */
class GtmAddOrRemoveCart implements ResolverInterface
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * GtmAddToCart constructor.
     *
     * @param Data $helperData
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Data $helperData,
        ProductRepositoryInterface $productRepository
    ) {
        $this->helperData        = $helperData;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->helperData->isEnabled()) {
            return '';
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $cartItems     = $value['cart_items'];
        $gtmAddToCart  = '';
        $gtmRemoveCart = '';
        $gaAddToCart   = '';
        $gaRemoveCart  = '';
        $fbAddToCart   = '';

        foreach ($cartItems as $cartItem) {
            if (isset($cartItem['data'])) {
                $sku = $cartItem['data']['sku'];
                $qty = $cartItem['data']['quantity'];
            } else {
                $sku = $cartItem['sku'];
                $qty = $cartItem['quantity'];
            }

            try {
                /** @var Product $product */
                $product = $this->productRepository->get($sku);

                if ($value['is_remove']) {
                    if ($this->helperData->getConfigGTM('enabled')) {
                        $removeGTMData = $this->helperData->getGTMRemoveFromCartData(
                            $product,
                            $qty
                        );

                        $gtmRemoveCart .= '<script>dataLayer.push(' . Data::jsonEncode($removeGTMData) . ')</script>';
                    }
                    if ($this->helperData->getConfigAnalytics('enabled')) {
                        $removeGaData = $this->helperData->getGARemoveFromCartData(
                            $product,
                            $qty
                        );

                        $gaRemoveCart .= '<script>dataLayer.push(' . Data::jsonEncode($removeGaData) . ')</script>';
                    }
                } else {
                    if ($this->helperData->getConfigGTM('enabled')) {
                        $productsGa4 = [];
                        $productsGTM = $this->helperData->getGTMAddToCartData($product, $qty);

                        if ($this->helperData->isEnabledGTMGa4()) {
                            $productsGa4 = $this->helperData->getGTMGa4AddToCartData($product, $qty);
                        }

                        $dataGTM = [
                            'event'     => 'addToCart',
                            'ecommerce' => [
                                'currencyCode' => $this->helperData->getCurrentCurrency(),
                                'add'          => [
                                    'products' => [$productsGTM]
                                ]
                            ]
                        ];

                        if ($this->helperData->isEnabledGTMGa4()) {
                            $dataGTM['ga4_event']          = 'add_to_cart';
                            $dataGTM['ecommerce']['items'] = [$productsGa4];
                        }
                        $gtmAddToCart .= '<script>dataLayer.push(' . Data::jsonEncode($dataGTM) . ')</script>';
                    }

                    if ($this->helperData->getConfigAnalytics('enabled')) {
                        $productsGA = $this->helperData->getGAAddToCartData($product, $qty);
                        $dataGA     = [
                            'items' => [$productsGA],
                        ];

                        $gaAddToCart .= '<script>gtag("event", "add_to_cart", ' . Data::jsonEncode($dataGA) . ')</script>';
                    }

                    if ($this->helperData->getConfigPixel('enabled')) {
                        $productsFB = $this->helperData->getFBAddToCartData($product, $qty);
                        $dataFB     = [
                            'content_ids'  => [$productsFB['id']],
                            'content_name' => [$productsFB['name']],
                            'content_type' => 'product',
                            'contents'     => [$productsFB],
                            'currency'     => $this->helperData->getCurrentCurrency(),
                            'value'        => (float) $productsFB['price']
                        ];

                        $fbAddToCart .= '<script>fbq("track", "AddToCart", ' . Data::jsonEncode($dataFB) . ')</script>';
                    }
                }
            } catch (Exception $e) {
                $gtmAddToCart = '';
                $gaAddToCart  = '';
                $fbAddToCart  = '';
            }
        }

        switch ($field->getName()) {
            case 'gtm_add_to_cart':
                return $gtmAddToCart;
            case 'ga_add_to_cart':
                return $gaAddToCart;
            case 'fb_add_to_cart':
                return $fbAddToCart;
            case 'gtm_remove_from_cart':
                return $gtmRemoveCart;
            case 'ga_remove_from_cart':
                return $gaRemoveCart;
            default:
                return '';
        }
    }
}

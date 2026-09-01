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

namespace Mageplaza\GoogleTagManager\Block\Tag;

use DateTime;
use DateTimeZone;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Mageplaza\GoogleTagManager\Block\TagManager;

/**
 * Class ManagerTag
 * @package Mageplaza\GoogleTagManager\Block\Tag
 */
class ManagerTag extends TagManager
{
    /**
     * Get GTM Id
     *
     * @param $storeId
     *
     * @return array|mixed
     */
    public function getTagId($storeId = null)
    {
        return $this->_helper->getConfigGTM('tag_id', $storeId);
    }

    /**
     * Check condition show page
     *
     * @return bool
     */
    public function canShowGtm()
    {
        return $this->_helper->isEnabled() && $this->_helper->getConfigGTM('enabled');
    }

    /**
     * Tag manager dataLayer
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getGtmDataLayer()
    {
        $action = $this->getRequest()->getFullActionName();
        switch ($action) {
            case 'cms_index_index':
                return $this->encodeJs($this->getHomeData());
            case 'catalogsearch_result_index':
                return $this->encodeJs($this->getSearchData());
            case 'catalog_category_view': // Product list page
                return $this->encodeJs($this->getCategoryData());
            case 'catalog_product_view': // Product detail view page
                return $this->encodeJs($this->getProductView());
            case 'checkout_index_index':  // Checkout page
                return $this->encodeJs($this->getCheckoutProductData('2', 'Checkout Page'));
            case 'checkout_cart_index':   // Shopping cart
                return $this->encodeJs($this->getCheckoutProductData('1', 'Shopping Cart'));
            case 'onestepcheckout_index_index': // Mageplaza One step check out page
                return $this->encodeJs($this->getCheckoutProductData('2', 'Checkout Page'));
            case 'checkout_onepage_success': // Purchase page
            case 'multishipping_checkout_success':
            case 'mpthankyoupage_index_index': // Mageplaza Thank you page
                return $this->encodeJs($this->getCheckoutSuccessData());
        }

        return $this->encodeJs($this->getDefaultData());
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getHomeData()
    {
        $data = [
            'ecommerce' => [
                'currencyCode' => $this->_helper->getCurrentCurrency()
            ]
        ];

        return $data;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getSearchData()
    {
        $data = [
            'ecommerce' => [
                'currencyCode' => $this->_helper->getCurrentCurrency()
            ]
        ];

        return $data;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCategoryData()
    {
        /** get current breadcrumb path name */
        $path          = $this->_helper->getBreadCrumbsPath();
        $products      = [];
        $productsGa4   = [];
        $result        = [];
        $itemsData     = [];
        $resultGa4     = [];
        $i             = 0;
        $categoryId    = $this->_registry->registry('current_category')->getId();
        $category      = $this->_category->load($categoryId);
        $storeId       = $category->getStore()->getId();
        $useIdOrSku    = $this->getUseIdOrSku($storeId);
        $loadedProduct = $this->getCategotyCollection($category);
        $this->_toolbar->setCollection($loadedProduct);

        $allItemsValue = 0;

        foreach ($loadedProduct as $item) {
            $i++;
            $allItemsValue            += $this->_helper->getPrice($item);
            $products[$i]['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
            $products[$i]['name']     = $item->getName();
            $products[$i]['price']    = $this->_helper->getPrice($item);
            $products[$i]['list']     = $category->getName();
            $products[$i]['position'] = $i;
            $products[$i]['category'] = $category->getName();

            if ($this->_helper->isEnabledBrand($item, $storeId)) {
                $products[$i]['brand'] = $this->_helper->getProductBrand($item);
            }

            if ($this->_helper->isEnabledVariant($item, $storeId)) {
                $products[$i]['variant'] = $this->_helper->getColor($item);
            }

            $products[$i]['path']          = implode(' > ', $path) . ' > ' . $item->getName();
            $products[$i]['category_path'] = implode(' > ', $path);
            $result[]                      = $products[$i];
            $itemsData[]                   = [
                'id'                       => $products[$i]['id'],
                'google_business_vertical' => 'retail'
            ];

            if ($this->_helper->isEnabledGTMGa4()) {
                $productsGa4[$i]['item_id']        = $useIdOrSku ? $item->getSku() : $item->getId();
                $productsGa4[$i]['item_name']      = $item->getName();
                $productsGa4[$i]['price']          = $this->_helper->getPrice($item);
                $productsGa4[$i]['item_list_name'] = $category->getName();
                $productsGa4[$i]['item_list_id']   = $category->getId();
                $productsGa4[$i]['index']          = $i;
                $productsGa4[$i]['quantity']       = $this->_helper->getQtySale($item);

                if ($this->_helper->isEnabledBrand($item, $storeId)) {
                    $productsGa4[$i]['item_brand'] = $this->_helper->getProductBrand($item);
                }

                if ($this->_helper->isEnabledVariant($item, $storeId)) {
                    $productsGa4[$i]['item_variant'] = $this->_helper->getColor($item);
                }

                if (!empty($path)) {
                    $j = null;
                    foreach ($path as $cat) {
                        $key                   = 'item_category' . $j;
                        $j                     = (int) $j;
                        $productsGa4[$i][$key] = $cat;
                        $j++;
                    }
                }

                $resultGa4[] = $productsGa4[$i];
            }
        }

        $data['remarketing_event'] = 'view_item_list';
        $data['value']             = $allItemsValue;
        $data['items']             = $itemsData;

        $data['ecommerce'] = [
            'currencyCode' => $this->_helper->getCurrentCurrency(),
            'impressions'  => $result
        ];

        if ($this->_helper->isEnabledGTMGa4()) {
            $data['ga4_event']          = 'view_item_list';
            $data['ecommerce']['items'] = $resultGa4;
        }

        return $data;
    }

    /**
     * Get GTM use ID or Sku
     *
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUseIdOrSku($storeId = null)
    {
        return $this->_helper->getConfigGTM('use_id_or_sku', $storeId);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductView()
    {
        $currentProduct = $this->_helper->getGtmRegistry()->registry('product');

        return $this->_helper->getProductDetailData($currentProduct);
    }

    /**
     * Get product data in checkout page
     *
     * @param string $step
     * @param string $option
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getCheckoutProductData($step, $option = 'Checkout')
    {
        $cart = $this->_cart;
        // retrieve quote items array
        $items       = $cart->getQuote()->getAllVisibleItems();
        $products    = [];
        $productsGa4 = [];
        $i           = 1;

        if (empty($items)) {
            return [];
        }

        foreach ($items as $item) {
            $products[] = $this->_helper->getProductCheckOutData($item);
            if ($this->_helper->isEnabledGTMGa4() && $step === '2') {
                $productGa4          = $this->_helper->getProductGa4CheckOutData($item);
                $productGa4['index'] = $i;
                $productsGa4[]       = $productGa4;
                $i++;
            }
        }

        $eCommProdId = [];
        foreach ($products as $product) {
            $eCommProdId[] = $product['id'];
        }

        $data = [
            'event'     => 'checkout',
            'ecommerce' => [
                'checkout' => [
                    'actionField' => [
                        'step'   => $step,
                        'option' => $option
                    ],
                    'products'    => $products
                ]
            ]
        ];

        if ($this->_helper->isEnabledGTMGa4() && $step === '2') {
            $data['ga4_event']          = 'begin_checkout';
            $data['ecommerce']['items'] = $productsGa4;
        }

        return $data;
    }

    /**
     * @return array|mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputException
     */
    protected function getCheckoutSuccessData()
    {
        $order = $this->_helper->getSessionManager()->getLastRealOrder();

        if ($this->isMultiShipping()) {
            $orderIds       = $this->getMultiShipping()->getOrderIds();
            $baseGrandTotal = 0;
            $data           = [];
            if ($orderIds) {
                foreach ($orderIds as $orderId) {
                    /** @var Order $or */
                    $or                          = $this->orderRepository->get($orderId);
                    $baseGrandTotal              += $this->_helper->calculateTotals($or);
                    $data[$or->getIncrementId()] = $this->getCheckoutSuccessDataEachOrder($or);
                    $data['enhanced']            = $this->getEnhancedConversionData($or);
                }
            }

            if ($this->_helper->isEnabledIgnoreOrders($this->_helper->getStoreId()) && $baseGrandTotal <= 0) {
                return [];
            }

            return $data;
        }

        if ($this->_helper->isEnabledIgnoreOrders($this->_helper->getStoreId())
            && $this->_helper->calculateTotals($order) <= 0) {
            return [];
        }

        $data             = $this->getCheckoutSuccessDataEachOrder($order);
        $data['enhanced'] = $this->getEnhancedConversionData($order);

        return $data;
    }

    /**
     * @param Order $order
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getCheckoutSuccessDataEachOrder($order)
    {
        $items = $order->getItemsCollection([], true);

        $products    = [];
        $productsGa4 = [];
        $skuItems    = [];
        $skuItemsQty = [];

        /** @var Item $item */
        foreach ($items as $item) {
            $productSku    = $item->getProduct()->getSku();
            $products[]    = $this->_helper->getProductOrderedData($item);
            $skuItems[]    = $productSku;
            $skuItemsQty[] = $productSku . ':' . (int) $item->getQtyOrdered();
            if ($this->_helper->isEnabledGTMGa4()) {
                $productsGa4[] = $this->_helper->getGa4ProductOrderedData($item);
            }
        }

        $itemsData = [];
        foreach ($products as $product) {
            $itemsData[] = [
                'id'                       => $product['id'],
                'google_business_vertical' => 'retail'
            ];
        }

        $data['remarketing_event'] = 'purchase';
        $data['value']             = $this->_helper->calculateTotals($order);
        $data['items']             = $itemsData;

        $createdAt = $this->timezone->date(
            new DateTime($order->getCreatedAt(), new DateTimeZone('UTC')),
            $this->localeResolver->getLocale(),
            true
        );

        $data['ecommerce'] = [
            'purchase'     => [
                'actionField' => [
                    'id'          => $order->getIncrementId(),
                    'affiliation' => $this->_helper->getAffiliationName(),
                    'order_id'    => $order->getIncrementId(),
                    'subtotal'    => $order->getSubtotal(),
                    'shipping'    => $order->getShippingAmount(),
                    'tax'         => $order->getTaxAmount(),
                    'revenue'     => $this->_helper->calculateTotals($order),
                    'discount'    => $order->getDiscountAmount(),
                    'coupon'      => (string) $order->getCouponCode(),
                    'created_at'  => $createdAt->format('Y-m-d H:i:s'),
                    'items'       => implode(';', $skuItems),
                    'items_qty'   => implode(';', $skuItemsQty)
                ],
                'products'    => $products
            ],
            'currencyCode' => $this->_helper->getCurrentCurrency()
        ];

        if ($this->_helper->isEnabledGTMGa4()) {
            $data['ga4_event']                   = 'purchase';
            $data['ecommerce']['transaction_id'] = $order->getIncrementId();
            $data['ecommerce']['affiliation']    = $this->_helper->getAffiliationName();
            $data['ecommerce']['value']          = $this->_helper->calculateTotals($order);
            $data['ecommerce']['tax']            = $order->getTaxAmount();
            $data['ecommerce']['shipping']       = $order->getShippingAmount();
            $data['ecommerce']['currency']       = $this->_helper->getCurrentCurrency();
            $data['ecommerce']['coupon']         = (string) $order->getCouponCode();
            $data['ecommerce']['items']          = $productsGa4;
        }

        return $data;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getDefaultData()
    {
        $data = [
            'ecommerce' => [
                'currencyCode' => $this->_helper->getCurrentCurrency()
            ]
        ];

        return $data;
    }

    /**
     * Get enhanced conversion tracking data
     *
     * @param $order
     *
     * @return array
     */
    public function getEnhancedConversionData($order)
    {
        $data   = [];
        /** @var Order $order */
        $addess = $order->getShippingAddress() ? : $order->getBillingAddress();

        $data['email']       = $addess->getEmail() ?: '';
        $data['first_name']  = $addess->getFirstname() ?: '';
        $data['last_name']   = $addess->getLastname() ?: '';
        $data['phone']       = $addess->getTelephone() ?: '';
        $data['street']      = implode(', ', $addess->getStreet()) ?: '';
        $data['city']        = $addess->getCity() ?: '';
        $data['region']      = $addess->getRegion() ?: '';
        $data['postal_code'] = $addess->getPostcode() ?: '';
        $data['country']     = $addess->getCountryId() ?: '';

        return $data;
    }
}

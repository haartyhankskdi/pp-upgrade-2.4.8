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

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Mageplaza\GoogleTagManager\Block\TagManager;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class PixelTag
 * @package Mageplaza\GoogleTagManager\Block\Tag
 */
class PixelTag extends TagManager
{

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getPixelId($storeId = null)
    {
        return $this->_helper->getConfigPixel('tag_id', $storeId);
    }

    /**
     * Can show pixel
     *
     * @return bool
     */
    public function canShowFbPixel()
    {
        return $this->_helper->isEnabled() && $this->_helper->getConfigPixel('enabled');
    }

    /**
     * @return array|null
     */
    public function getFbPageInfo()
    {
        try {
            $action = $this->getRequest()->getFullActionName();
            switch ($action) {
                case 'cms_index_index':
                    return $this->getHomeData();
                case 'catalogsearch_result_index':
                    return $this->getSearchData();
                case 'catalog_product_view':
                    return $this->getProductView();
                case 'checkout_index_index':
                case 'checkout_cart_index':
                    return $this->getFBCheckoutProductData();
                case 'onestepcheckout_index_index':
                    return $this->_helper->moduleIsEnable('Mageplaza_Osc') ? $this->getFBCheckoutProductData() : null;
                case 'checkout_onepage_success':
                case 'multishipping_checkout_success':
                case 'mpthankyoupage_index_index': // Mageplaza Thank you page
                    return $this->getCheckoutSuccessData();
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getHomeData()
    {
        $data ['ecommerce'] = [
            'currencyCode' => $this->_helper->getCurrentCurrency()
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
        $productSearch   = $this->_getProductCollection();
        $sortDir         = $this->getRequest()->getParam('product_list_dir')
            ? $this->getRequest()->getParam('product_list_dir') : 'desc';
        $listFilterOrder = $this->getRequest()->getParam('product_list_order');
        if ($listFilterOrder) {
            $productSearch->addAttributeToSort($listFilterOrder, $sortDir);
        }
        $productSearch->setCurPage($this->getPageNumber())->setPageSize($this->getPageLimit());
        $products   = [];
        $productIds = [];
        $values     = 0;
        $storeId    = $this->_helper->getStoreId();
        $useIdOrSku = $this->getUseIdOrSku($storeId);

        foreach ($productSearch as $value) {
            $productIds[]    = $useIdOrSku ? $value->getSku() : $value->getId();
            $sub             = [];
            $sub['id']       = $useIdOrSku ? $value->getSku() : $value->getId();
            $sub['quantity'] = $this->_helper->getQtySale($value);
            $sub['name']     = $value->getName();
            $sub['price']    = $this->_helper->getPrice($value);
            $products[]      = $sub;
            $values          += $this->_helper->getPrice($value);
        }

        $data = [
            'track_type' => 'Search',
            'data'       => [
                'content_ids'  => $productIds,
                'content_name' => 'Search',
                'content_type' => 'product',
                'contents'     => $products,
                'currency'     => $this->_helper->getCurrentCurrency(),
                'value'        => $values
            ]
        ];

        return $data;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getUseIdOrSku($storeId = null)
    {
        return $this->_helper->getConfigPixel('use_id_or_sku', $storeId);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductView()
    {
        $currentProduct = $this->_helper->getGtmRegistry()->registry('product');
        $fbData         = $this->_helper->getFBProductView($currentProduct);
        $data           = [
            'track_type' => 'ViewContent',
            'data'       => $fbData
        ];

        return $data;
    }

    /**
     * Get Checkout Data
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBCheckoutProductData()
    {
        $items      = $this->_cart->getQuote()->getAllVisibleItems();
        $products   = [];
        $productIds = [];
        $value      = 0;
        $storeId    = $this->_helper->getStoreId();
        $useIdOrSku = $this->getUseIdOrSku($storeId);

        if (empty($items)) {
            return [];
        }

        foreach ($items as $item) {
            $productIds[] = $useIdOrSku ? $item->getSku() : $item->getProductId();
            $productInfo  = $this->_helper->getFBProductCheckOutData($item);
            $products[]   = $productInfo;
            $value        += $productInfo['price'] * $productInfo['quantity'];
        }

        $data = [
            'track_type' => 'InitiateCheckout',
            'data'       => [
                'content_ids'  => $productIds,
                'content_name' => 'checkout',
                'content_type' => 'product',
                'contents'     => $products,
                'currency'     => $this->_helper->getCurrentCurrency(),
                'value'        => $value
            ]
        ];

        return $data;
    }

    /**
     * @return array
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

        return $this->getCheckoutSuccessDataEachOrder($order);
    }

    /**
     * @param Order $order
     *
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getCheckoutSuccessDataEachOrder($order)
    {
        $products   = [];
        $productIds = [];
        $storeId    = $this->_helper->getStoreId();
        $useIdOrSku = $this->getUseIdOrSku($storeId);
        $items      = $order->getItemsCollection([], true);
        foreach ($items as $item) {
            $productIds[] = $useIdOrSku ? $item->getSku() : $item->getProductId();
            $products[]   = $this->_helper->getFBProductOrderedData($item);
        }

        $data = [
            'track_type' => 'Purchase',
            'data'       => [
                'content_ids'  => $productIds,
                'content_name' => 'Purchase',
                'content_type' => 'product',
                'contents'     => $products,
                'currency'     => $this->_helper->getCurrentCurrency(),
                'value'        => $this->_helper->calculateTotals($order)
            ]
        ];

        return $data;
    }

    /**
     * @return false|string|null
     */
    public function getFBAddToCartData()
    {
        if ($this->_helper->getSessionManager()->getFBAddToCartData()) {
            return json_encode($this->_helper->getSessionManager()->getFBAddToCartData());
        }

        return null;
    }

    /**
     * Get Facebook Pixel Advanced Matching Data
     *
     * @return array|null
     */
    public function getAdvancedMatchingData()
    {
        $customerSession = $this->customerSession->create();
        if ($customerSession->isLoggedIn()) {
            $customer               = $customerSession->getCustomer();
            $defaultShippingAddress = $customer->getDefaultShippingAddress();

            $advancedMatchingData = [
                'em' => strtolower($customer->getEmail()),
                'fn' => strtolower($customer->getFirstname()),
                'ln' => strtolower($customer->getLastname()),
                'ge' => $customer->getGender() != 3 ? ($customer->getGender() == 2 ? 'f' : 'm') : '',
            ];

            if ($customer->getDob()) {
                $advancedMatchingData['db'] = str_replace('-', '', $customer->getDob());
            }

            if ($defaultShippingAddress) {
                $advancedMatchingData['country'] = strtolower($defaultShippingAddress->getCountryId());
                $advancedMatchingData['ct']      = strtolower($defaultShippingAddress->getCity());
                $advancedMatchingData['st']      = strtolower($defaultShippingAddress->getRegionCode());
                $advancedMatchingData['zp']      = strtolower($defaultShippingAddress->getPostcode());
                $advancedMatchingData['ph']      = preg_replace(
                    '~^(\+)|\D~',
                    '\1',
                    $defaultShippingAddress->getTelephone()
                );
            }

            return $advancedMatchingData;
        }

        return null;
    }

    /**
     * @return Data
     */
    public function getHelperData()
    {
        return $this->_helper;
    }
}

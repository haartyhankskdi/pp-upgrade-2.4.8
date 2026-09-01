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
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Mageplaza\GoogleTagManager\Block\TagManager;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class AnalyticsTag
 * @package Mageplaza\GoogleTagManager\Block\Tag
 */
class AnalyticsTag extends TagManager implements IdentityInterface
{
    const RELATED   = 'related';
    const UPSELL    = 'up_sell';
    const CROSSSELL = 'cross_sell';
    const SEARCH    = 'search';

    /**
     * @return bool
     */
    public function canShowAnalytics()
    {
        return $this->_helper->isEnabled() && $this->_helper->getConfigAnalytics('enabled')
            && $this->_helper->isUserNotAllowSaveCookie();
    }

    /**
     * @return mixed
     */
    public function getTagId()
    {
        return trim($this->_helper->getConfigAnalytics('tag_id'));
    }

    /**
     * @return mixed
     */
    public function getSubTagId()
    {
        return $this->_helper->getConfigAnalytics('second_tag_id');
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getCurrency()
    {
        return $this->_helper->getCurrentCurrency();
    }

    /**
     * @return string
     */
    public function isLinkAttribution()
    {
        return $this->_helper->getConfigAnalytics('link_attribution') ? 'true' : 'false';
    }

    /**
     * @return string
     */
    public function isAnonymizeIp()
    {
        return $this->_helper->getConfigAnalytics('ip_anonymization') ? 'true' : 'false';
    }

    /**
     * @return string
     */
    public function isDisplayFeatures()
    {
        return $this->_helper->getConfigAnalytics('display_features') ? 'true' : 'false';
    }

    /**
     * @return bool
     */
    public function isLinker()
    {
        return $this->_helper->getConfigAnalytics('linker')
            && trim($this->_helper->getConfigAnalytics('linker_domain'), ',');
    }

    /**
     * @return string
     */
    public function getLinkerDomains()
    {
        $domains = explode(',', $this->_helper->getConfigAnalytics('linker_domain'));
        $links   = '[';
        foreach ($domains as $key => $domain) {
            if (empty($domain)) {
                unset($domains[$key]);
            } else {
                if ($key === count($domains) - 1) {
                    $links .= '"' . $domain . '"';
                } else {
                    $links .= '"' . $domain . '",';
                }
            }
        }
        $links .= ']';

        return $links;
    }

    // phpcs:disable Generic.Metrics.NestingLevel

    /**
     * @return array|mixed
     */
    public function getAnalyticsData()
    {
        try {
            $action        = $this->getRequest()->getFullActionName();
            $data          = [];
            $trackPosition = explode(
                ',',
                $this->_helper->getConfigAnalytics('track_position', $this->_helper->getStoreId())
            );
            switch ($action) {
                case 'catalogsearch_advanced_result':
                case 'catalogsearch_result_index':
                    if (!in_array(self::SEARCH, $trackPosition)) {
                        return $data;
                    }

                    $productSearch   = $this->_getProductCollection();
                    if ($action === 'catalogsearch_advanced_result') {
                        $productSearch = $this->_getProductAdvancedCollection();
                    }
                    $sortDir         = $this->getRequest()->getParam('product_list_dir')
                        ? $this->getRequest()->getParam('product_list_dir') : 'desc';
                    $listFilterOrder = $this->getRequest()->getParam('product_list_order');
                    if ($listFilterOrder) {
                        $productSearch->addAttributeToSort($listFilterOrder, $sortDir);
                    }
                    $productSearch->setCurPage($this->getPageNumber())
                        ->setPageSize($this->getPageLimit());
                    $products = [];
                    $sub      = 1;
                    foreach ($productSearch as $product) {
                        $products[] = $this->_helper->getItems($product, 'Search Results', $sub++);
                    }

                    $data = [
                        'event_name' => ['view_item_list'],
                        'data'       => [
                            'items' => $products,
                        ]
                    ];

                    return $data;
                case 'catalog_category_view': // Product list page
                    /** get current breadcrumb path name */
                    $products      = [];
                    $i             = 0;
                    $categoryId    = $this->_registry->registry('current_category')->getId();
                    $category      = $this->_category->load($categoryId);
                    $loadedProduct = $this->getCategotyCollection($category);
                    $this->_toolbar->setCollection($loadedProduct);

                    foreach ($loadedProduct as $item) {
                        $products[] = $this->_helper->getItems($item, $category->getName(), ++$i);
                    }

                    $data = [
                        'event_name' => ['view_item_list'],
                        'data'       => [
                            'items' => $products,
                        ]
                    ];

                    return $data;
                case 'catalog_product_view': // Product detail view page
                    $currentProduct = $this->_helper->getGtmRegistry()->registry('product');
                    $products       = $this->_helper->getViewProductData($currentProduct);
                    $related        = $this->_helper->getRelatedProductData($currentProduct);
                    $upSell         = $this->_helper->getUpSellProductData($currentProduct);
                    $data           = [
                        'event_name' => ['view_item'],
                        'data'       => [
                            'items' => [$products],
                        ]
                    ];
                    if (count($related) && in_array(self::RELATED, $trackPosition)) {
                        $data['related'] = [
                            'event'          => 'view_item_list',
                            'item_list_id'   => 'related_products',
                            'item_list_name' => 'Related products',
                            'items'          => $related,
                        ];
                    }
                    if (count($upSell) && in_array(self::UPSELL, $trackPosition)) {
                        $data['up_sell'] = [
                            'event'          => 'view_item_list',
                            'item_list_id'   => 'upsell_products',
                            'item_list_name' => 'Up-sell products',
                            'items'          => $upSell,
                        ];
                    }

                    return $data;
                case 'checkout_index_index':  // Checkout page
                    return $this->getCheckoutProductData(false);
                case 'checkout_cart_index':   // Shopping cart
                    return $this->getCheckoutProductData(true);
                case 'onestepcheckout_index_index': // Mageplaza One step check out page
                    return $this->_helper->moduleIsEnable('Mageplaza_Osc') ? $this->getCheckoutProductData(false) : [];
                case 'checkout_onepage_success': // Purchase page
                case 'multishipping_checkout_success':
                case 'mpthankyoupage_index_index': // Mageplaza Thank you page
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

                        if ($this->_helper->isEnabledIgnoreOrders($this->_helper->getStoreId())
                            && $baseGrandTotal <= 0) {
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

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param bool $step
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCheckoutProductData($step)
    {
        // retrieve quote items array
        $items = $this->_cart->getQuote()->getAllVisibleItems();
        if (empty($items)) {
            return [];
        }

        $products = [];
        foreach ($items as $item) {
            $products[] = $this->_helper->getCheckoutProductData($item);
        }

        $data = [
            'event_name' => $step ? ['begin_checkout'] : ['checkout_progress'],
            'data'       => [
                'items'         => $products,
                'coupon'        => $this->_cart->getQuote()->getCouponCode() ?: '',
                'checkout_step' => $step ? 1 : 2,
            ],
            'step'       => [
                'event'           => 'set_checkout_option',
                'checkout_step'   => $step ? 1 : 2,
                'checkout_option' => $step ? 'begin_checkout' : 'checkout_progress'
            ]
        ];

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
        $products = [];
        $items    = $order->getItemsCollection([], true);
        foreach ($items as $item) {
            $products[] = $this->_helper->getCheckoutProductData($item);
        }

        $data = [
            'event_name' => ['purchase'],
            'data'       => [
                'transaction_id' => $order->getIncrementId(),
                'value'          => $this->_helper->calculateTotals($order),
                'currency'       => $this->_helper->getCurrentCurrency(),
                'tax'            => $order->getTaxAmount(),
                'shipping'       => $order->getShippingAmount(),
                'items'          => $products
            ]
        ];

        return $data;
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return ['mp_gtm_analytics'];
    }

    /**
     * @return array
     */
    public function getDimensionsAndMetrics()
    {
        $values                 = [];
        $customMap['page_path'] = $this->_helper->getCurrentUrl();
        $customDimensions       = $this->_helper->getDimensions();
        $customMetrics          = $this->_helper->getMetrics();
        $dimensionsOptions      = $customDimensions ? Data::jsonDecode($customDimensions) : [];
        $metricsOptions         = $customMetrics ? Data::jsonDecode($customMetrics) : [];

        [$customMap, $values] = $this->getDimensionsMetricsInformation($dimensionsOptions, $customMap, $values);
        [$customMap, $values] = $this->getDimensionsMetricsInformation($metricsOptions, $customMap, $values, false);

        return [$customMap, $values];
    }

    /**
     * @param array $Options
     * @param array $customMap
     * @param array $values
     * @param bool $isDimensions
     *
     * @return array
     */
    protected function getDimensionsMetricsInformation(
        array $Options,
        array $customMap,
        array $values,
        $isDimensions = true
    ) {
        if (!isset($customMap['custom_map'])) {
            $customMap['custom_map'] = [];
        }
        if (isset($Options['option']['value']) && $Options['option']['value']) {
            foreach ($Options['option']['value'] as $option) {
                $key = 'metric' . $option['index'];
                if ($isDimensions) {
                    $key = 'dimension' . $option['index'];
                }
                if ($this->_helper->getValueDimensionsMetrics($option['value'])) {
                    $k                             = $isDimensions
                        ? 'dimension_' . $option['name'] : 'metric_' . $option['name'];
                    $customMap['custom_map'][$key] = $k;
                    $values[$k]                    = $this->_helper->getValueDimensionsMetrics($option['value']);
                }
            }
        }

        return [$customMap, $values];
    }
}

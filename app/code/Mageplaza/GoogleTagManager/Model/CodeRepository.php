<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
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

namespace Mageplaza\GoogleTagManager\Model;

use DateTime;
use DateTimeZone;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer\Search;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Search\Helper\Data as SearchData;
use Mageplaza\GoogleTagManager\Api\CodeRepositoryInterface;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;

/**
 * Class CodeRepository
 * @package Mageplaza\GoogleTagManager\Model
 */
class CodeRepository implements CodeRepositoryInterface
{
    const RELATED   = 'related';
    const UPSELL    = 'up_sell';
    const CROSSSELL = 'cross_sell';
    const SEARCH    = 'search';

    /**
     * @var bool
     */
    protected $isRest = true;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchData
     */
    protected $searchHelper;

    /**
     * @var StringUtils
     */
    protected $string;

    /**
     * @var Search
     */
    protected $search;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * CodeRepository constructor.
     *
     * @param HelperData $helperData
     * @param Category $category
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param TimezoneInterface $timezone
     * @param ResolverInterface $resolver
     * @param ProductRepositoryInterface $productRepository
     * @param SearchData $searchHelper
     * @param StringUtils $string
     * @param Search $search
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Order $order
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     */
    public function __construct(
        HelperData $helperData,
        Category $category,
        CartRepositoryInterface $quoteRepository,
        OrderRepositoryInterface $orderRepository,
        TimezoneInterface $timezone,
        ResolverInterface $resolver,
        ProductRepositoryInterface $productRepository,
        SearchData $searchHelper,
        StringUtils $string,
        Search $search,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Order $order,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->helperData            = $helperData;
        $this->category              = $category;
        $this->quoteRepository       = $quoteRepository;
        $this->orderRepository       = $orderRepository;
        $this->timezone              = $timezone;
        $this->resolver              = $resolver;
        $this->productRepository     = $productRepository;
        $this->searchHelper          = $searchHelper;
        $this->string                = $string;
        $this->search                = $search;
        $this->quoteIdMaskFactory    = $quoteIdMaskFactory;
        $this->order                 = $order;
        $this->filterBuilder         = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder    = $filterGroupBuilder;
    }

    /**
     * @param string $type
     * @param string $action
     * @param string $id
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCodeGraphQl($type, $action, $id)
    {
        $this->isRest   = false;
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->getCode($type, $action, $id, $searchCriteria);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getGTMCodeHome()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->getCode('GTM', 'home', null, $searchCriteria);
    }

    /**
     * @param string $type
     * @param string $action
     * @param string $id
     * @param SearchCriteriaInterface|null $searchCriteria
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCode($type, $action, $id, SearchCriteriaInterface $searchCriteria = null)
    {
        $isMultiShipping = $action === 'multishippingcheckoutsuccess';

        if (!$this->helperData->isEnabled()) {
            throw new LocalizedException(__('The module is disabled'));
        }

        if ($searchCriteria === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }

        switch ($type) {
            case 'GTM':
                if (!$this->helperData->getConfigGTM('enabled')) {
                    throw new LocalizedException(__('Google Tag Manager is disabled'));
                }
                $html       = '';
                $tagId      = $this->helperData->getConfigGTM('tag_id');
                $canShowGtm = $this->helperData->isEnabled() && $this->helperData->getConfigGTM('enabled');
                $data       = $this->getData($searchCriteria, $type, $action, $id);

                if ($tagId && $canShowGtm && !empty($data)) {
                    $html = $this->getCodeGTM($tagId, $data, $isMultiShipping);
                }

                return $html;
            case 'GA':
                if (!$this->helperData->getConfigAnalytics('enabled')) {
                    throw new LocalizedException(__('Google Analytics is disabled'));
                }
                $html             = '';
                $tagId            = $this->helperData->getConfigAnalytics('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigAnalytics('enabled');
                $data             = $this->getData($searchCriteria, $type, $action, $id);

                if ($tagId && $canShowAnalytics && !empty($data)) {
                    $html = $this->getCodeGA($data, $isMultiShipping);
                }

                return $html;
            case 'FbPixel':
                if (!$this->helperData->getConfigPixel('enabled')) {
                    throw new LocalizedException(__('Facebook Pixed is disabled'));
                }
                $html             = '';
                $tagId            = $this->helperData->getConfigPixel('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigPixel('enabled');
                $data             = $this->getData($searchCriteria, $type, $action, $id);

                if ($tagId && $canShowAnalytics && !empty($data)) {
                    $html = $this->getCodeFbPixel($data, $isMultiShipping);
                }

                return $html;
            default:
                throw new LocalizedException(__('The type is incorrect!'));
        }
    }

    /**
     * @param string $type
     *
     * @return string
     * @throws LocalizedException
     */
    public function getHead($type)
    {
        if (!$this->helperData->isEnabled()) {
            throw new LocalizedException(__('The module is disabled'));
        }

        switch ($type) {
            case 'GTM':
                if (!$this->helperData->getConfigGTM('enabled')) {
                    throw new LocalizedException(__('Google Tag Manager is disabled'));
                }
                $html       = '';
                $tagId      = $this->helperData->getConfigGTM('tag_id');
                $canShowGtm = $this->helperData->isEnabled() && $this->helperData->getConfigGTM('enabled');

                if ($tagId && $canShowGtm) {
                    $html = "<script>
                                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                                })(window,document,'script','dataLayer','" . $tagId . "');
                            </script>";
                }

                return $html;
            case 'GA':
                if (!$this->helperData->getConfigAnalytics('enabled')) {
                    throw new LocalizedException(__('Google Analytics is disabled'));
                }
                $html             = '';
                $tagId            = $this->helperData->getConfigAnalytics('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigAnalytics('enabled');

                if ($tagId && $canShowAnalytics) {
                    try {
                        $currentCurrency = $this->helperData->getCurrentCurrency();
                    } catch (Exception $e) {
                        $currentCurrency = '';
                    }
                    $isLinkAttribution = $this->helperData->getConfigAnalytics('link_attribution') ? 'true' : 'false';
                    $isAnonymizeIp     = $this->helperData->getConfigAnalytics('ip_anonymization') ? 'true' : 'false';
                    $isDisplayFeatures = $this->helperData->getConfigAnalytics('display_features') ? 'true' : 'false';

                    $html = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $tagId . '"></script>';
                    $html .= "<script>
                                window.dataLayer = window.dataLayer || [];
                                function gtag() {dataLayer.push(arguments);}
                                gtag('js', new Date());
                                gtag('set', {'currency': '" . $currentCurrency . "'});
                                gtag('set', {'link_attribution': " . $isLinkAttribution . "});
                                gtag('set', {'anonymize_ip': " . $isAnonymizeIp . "});
                                gtag('set', {'allow_ad_personalization_signals': " . $isDisplayFeatures . "});
                                gtag('config', '" . $tagId . "');";

                    if ($this->isLinker()) {
                        $html .= "gtag('set', {'linker': {'domains':" . $this->getLinkerDomains() . "}});";
                    }

                    if ($this->getSubTagId()) {
                        $subTagId = $this->helperData->getConfigAnalytics('second_tag_id');
                        $html     .= "gtag('config', '" . $subTagId . "');";
                    }

                    $html .= '</script>';
                }

                return $html;
            case 'FbPixel':
                if (!$this->helperData->getConfigPixel('enabled')) {
                    throw new LocalizedException(__('Facebook Pixed is disabled'));
                }
                $html             = '';
                $tagId            = $this->helperData->getConfigPixel('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigPixel('enabled');

                if ($tagId && $canShowAnalytics) {
                    $html = "<script>
                                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                                document,'script','https://connect.facebook.net/en_US/fbevents.js');
                                fbq('init', '" . $tagId . "');
                                fbq('track', 'PageView');
                            </script>
                            <noscript>
                                <img height='1' width='1' style='display:none'
                                alt='Facebook Pixel'
                                src='https://www.facebook.com/tr?id=" . $tagId . "&ev=PageView&noscript=1'/>
                            </noscript>";
                }

                return $html;
            default:
                return '';
        }
    }

    /**
     * @return mixed
     */
    public function getSubTagId()
    {
        return $this->helperData->getConfigAnalytics('second_tag_id');
    }

    /**
     * @return bool
     */
    public function isLinker()
    {
        return $this->helperData->getConfigAnalytics('linker')
            && trim($this->helperData->getConfigAnalytics('linker_domain'), ',');
    }

    /**
     * @return string
     */
    public function getLinkerDomains()
    {
        $domains = explode(',', $this->helperData->getConfigAnalytics('linker_domain'));
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

    /**
     * @param array $data
     * @param bool $isMultiShipping
     *
     * @return string
     */
    public function getCodeFbPixel($data, $isMultiShipping)
    {
        $html = '';

        if ($isMultiShipping) {
            foreach ($data as $dt) {
                if (isset($dt['track_type'])) {
                    $html .= '<script>fbq("track", "'
                        . $dt['track_type'] . '", ' . $this->encodeJs($dt['data']) . ')</script>';
                }
            }
        } else {
            if (isset($data['track_type'])) {
                $html .= '<script>fbq("track", "'
                    . $data['track_type'] . '", ' . $this->encodeJs($data['data']) . ')</script>';
            }
        }

        return $html;
    }

    /**
     * @param array $data
     * @param bool $isMultiShipping
     *
     * @return string
     */
    public function getCodeGA($data, $isMultiShipping)
    {
        $html = '';

        if ($isMultiShipping) {
            foreach ($data as $dt) {
                if (isset($dt) && !empty($dt['event_name'])) {
                    foreach ($dt['event_name'] as $event) {
                        $html .= '<script>gtag("event", "' . $event
                            . '", ' . $this->encodeJs($dt['data']) . ');</script>';
                    }
                }
            }
        } else {
            if (isset($data) && !empty($data['event_name'])) {
                foreach ($data['event_name'] as $event) {
                    $html .= '<script>gtag("event", "' . $event
                        . '", ' . $this->encodeJs($data['data']) . ');</script>';

                    if (!empty($data['step'])) {
                        $html .= '<script>
                                    gtag(
                                        "event",
                                        "' . $data['step']['event'] . '",
                                        {
                                            "checkout_step": ' . $data['step']['checkout_step'] . ',
                                            "checkout_option": "' . $data['step']['checkout_option'] . '"
                                        }
                                    )
                                  </script>';
                    }
                }
            }
        }

        return $html;
    }

    /**
     * @param string $tagId
     * @param string $data
     * @param bool $isMultiShipping
     *
     * @return string
     */
    public function getCodeGTM($tagId, $data, $isMultiShipping)
    {
        $html = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='
            . $tagId . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';

        if ($isMultiShipping) {
            $data = $this->decodeJs($data);
            foreach ($data as $dt) {
                $html .= '<script>dataLayer.push(' . $this->encodeJs($dt) . ');</script>';
            }
        } else {
            $html .= '<script>dataLayer.push(' . $data . ');</script>';
        }

        return $html;
    }

    /**
     * encode JS
     *
     * @param array $data
     *
     * @return string
     */
    public function encodeJs($data)
    {
        return HelperData::jsonEncode($data);
    }

    /**
     * decode JS
     *
     * @param string $data
     *
     * @return mixed
     */
    public function decodeJs($data)
    {
        return HelperData::jsonDecode($data);
    }

    /**
     * @param string $type
     * @param null $action
     * @param null $id
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return array|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getData($searchCriteria, $type = 'GTM', $action = null, $id = null)
    {
        switch ($action) {
            case 'home':
                return $this->getHomeData($type);
            case 'catalogsearch':
                return $this->getSearchData($searchCriteria, $type, $id);
            case 'category':
                return $this->getCategoryData($searchCriteria, $type, $id);
            case 'product':
                return $this->getProductView($type, $id);
            case 'checkoutindex':
                $step = '2';
                if ($type === 'GA') {
                    $step = false;
                }

                return $this->getCheckoutProductData($type, $id, $step, 'Checkout Page');
            case 'checkoutcart':
                $step = '1';
                if ($type === 'GA') {
                    $step = true;
                }

                return $this->getCheckoutProductData($type, $id, $step, 'Shopping Cart');
            case 'mponestepcheckout':
                $step = '2';
                if ($type === 'GA') {
                    $step = false;
                }

                return $this->helperData->moduleIsEnable('Mageplaza_Osc') ?
                    $this->getCheckoutProductData($type, $id, $step, 'Checkout Page') : [];
            case 'checkoutsuccess':
            case 'multishippingcheckoutsuccess':
                return $this->getCheckoutSuccessData($type, $action, $id);
            case 'mpthankyoupage':
                return $this->helperData->moduleIsEnable('Mageplaza_ThankYouPage') ?
                    $this->getCheckoutSuccessData($type, $action, $id) : [];
            default:
                return $this->getDefaultData($type);
        }
    }

    /**
     * @param string $type
     *
     * @return array|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getDefaultData($type)
    {
        switch ($type) {
            case 'GTM':
                $data = [
                    'ecomm_pagetype' => 'other',
                    'ecommerce'      => [
                        'currencyCode' => $this->helperData->getCurrentCurrency()
                    ]
                ];

                $data = $this->encodeJs($data);

                break;
            default:
                throw new LocalizedException(__('The action is incorrect!'));
        }

        return $data;
    }

    /**
     * @param string $type
     *
     * @return array|string
     */
    protected function getHomeData($type)
    {
        try {
            switch ($type) {
                case 'GTM':
                    $data = [
                        'ecomm_pagetype' => 'home',
                        'ecommerce'      => [
                            'currencyCode' => $this->helperData->getCurrentCurrency()
                        ]
                    ];
                    $data = $this->encodeJs($data);

                    break;
                case 'FbPixel':
                    $data['ecommerce'] = [
                        'currencyCode' => $this->helperData->getCurrentCurrency()
                    ];
                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param array $products
     *
     * @return string
     */
    public function getCodeSearchDataGraphQl($type, $products)
    {
        $html = '';
        $data = $this->getSearchDataGraphQl($type, $products);

        $tagId      = $this->helperData->getConfigPixel('tag_id');
        $canShowGtm = $this->helperData->isEnabled() && $this->helperData->getConfigPixel('enabled');
        if ($tagId && $canShowGtm && !empty($data)) {
            $html = $this->getCodeFbPixel($data, false);
        }

        if ($type === 'GA') {
            $tagId      = $this->helperData->getConfigAnalytics('tag_id');
            $canShowGtm = $this->helperData->isEnabled() && $this->helperData->getConfigAnalytics('enabled');
            if ($tagId && $canShowGtm && !empty($data)) {
                $html = $this->getCodeGA($data, false);
            }
        }

        return $html;
    }

    /**
     * @param string $type
     * @param array $products
     *
     * @return array
     */
    protected function getSearchDataGraphQl($type, $products)
    {
        try {
            switch ($type) {
                case 'GA':
                case 'FbPixel':
                    if ($type === 'GA') {
                        $items = [];
                        $sub   = 1;

                        /** @var Product $product */
                        foreach ($products as $value) {
                            $product = $this->productRepository->get($value['sku']);
                            $items[] = $this->helperData->getItems($product, 'Search Results', $sub++);
                        }

                        $data = [
                            'event_name' => ['view_item_list'],
                            'data'       => [
                                'items' => $items,
                            ]
                        ];
                    } else {
                        $contents   = [];
                        $productIds = [];
                        $values     = 0;
                        $useIdOrSku = $this->getUseIdOrSku($type);
                        foreach ($products as $product) {
                            $value           = $this->productRepository->get($product['sku']);
                            $productIds[]    = $useIdOrSku ? $value->getSku() : $value->getId();
                            $sub             = [];
                            $sub['id']       = $useIdOrSku ? $value->getSku() : $value->getId();
                            $sub['quantity'] = $this->helperData->getQtySale($value);
                            $sub['name']     = $value->getName();
                            $sub['price']    = $this->helperData->getPrice($value);
                            $contents[]      = $sub;
                            $values          += $this->helperData->getPrice($value);
                        }

                        $data = [
                            'track_type' => 'Search',
                            'data'       => [
                                'content_ids'  => $productIds,
                                'content_name' => 'Search',
                                'content_type' => 'product',
                                'contents'     => $contents,
                                'currency'     => $this->helperData->getCurrentCurrency(),
                                'value'        => $values
                            ]
                        ];
                    }

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $type
     * @param string $id
     *
     * @return array|string
     */
    protected function getSearchData($searchCriteria, $type, $id)
    {
        try {
            switch ($type) {
                case 'GTM':
                    $data = [
                        'ecomm_pagetype' => 'searchresults',
                        'ecommerce'      => [
                            'currencyCode' => $this->helperData->getCurrentCurrency()
                        ]
                    ];
                    $data = $this->encodeJs($data);

                    break;
                case 'GA':
                case 'FbPixel':
                    $minQueryLength  = $this->searchHelper->getMinQueryLength();
                    $thisQueryLength = $this->string->strlen($id);
                    if (!$thisQueryLength || $minQueryLength !== '' && $thisQueryLength < $minQueryLength) {
                        return [];
                    }

                    $collection = $this->search->getProductCollection();

                    if ($searchCriteria->getPageSize()) {
                        $collection->setPageSize($searchCriteria->getPageSize());
                    }

                    if ($searchCriteria->getCurrentPage()) {
                        $collection->setCurPage($searchCriteria->getCurrentPage());
                    }

                    if ($searchCriteria->getSortOrders()) {
                        foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                            $collection->setOrder($sortOrder->getField(), $sortOrder->getDirection());
                        }
                    }

                    /** @var Collection $products */
                    $products = $collection->addSearchFilter($id);

                    if ($type === 'GA') {
                        $items = [];
                        $sub   = 1;

                        /** @var Product $product */
                        foreach ($products->getItems() as $product) {
                            $items[] = $this->helperData->getItems($product, 'Search Results', $sub++);
                        }

                        $data = [
                            'event_name' => ['view_item_list'],
                            'data'       => [
                                'items' => $items,
                            ]
                        ];
                    } else {
                        $contents   = [];
                        $productIds = [];
                        $values     = 0;
                        $useIdOrSku = $this->getUseIdOrSku($type);
                        /** @var Product $value */
                        foreach ($products->getItems() as $value) {
                            $productIds[]    = $useIdOrSku ? $value->getSku() : $value->getId();
                            $sub             = [];
                            $sub['id']       = $useIdOrSku ? $value->getSku() : $value->getId();
                            $sub['quantity'] = $this->helperData->getQtySale($value);
                            $sub['name']     = $value->getName();
                            $sub['price']    = $this->helperData->getPrice($value);
                            $contents[]      = $sub;
                            $values          += $this->helperData->getPrice($value);
                        }

                        $data = [
                            'track_type' => 'Search',
                            'data'       => [
                                'content_ids'  => $productIds,
                                'content_name' => 'Search',
                                'content_type' => 'product',
                                'contents'     => $contents,
                                'currency'     => $this->helperData->getCurrentCurrency(),
                                'value'        => $values
                            ]
                        ];
                    }

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param null $storeId
     *
     * @return array|mixed
     */
    public function getUseIdOrSku($type, $storeId = null)
    {
        return $type === 'GTM'
            ? $this->helperData->getConfigGTM('use_id_or_sku', $storeId)
            : $this->helperData->getConfigPixel('use_id_or_sku', $storeId);
    }

    // phpcs:disable Generic.Metrics.NestingLevel

    /**
     * @param string $type
     * @param string $id
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return array
     */
    protected function getCategoryData($searchCriteria, $type, $id)
    {
        try {
            $category = $this->category->load($id);
            if (!$category->getIsActive()) {
                return [];
            }
            $filterGroups = $searchCriteria->getFilterGroups();
            foreach ($filterGroups as $key => $filterGroup) {
                foreach ($filterGroup->getFilters() as $filter) {
                    if (in_array($filter->getField(), ['category_id', 'visibility'], true)) {
                        unset($filterGroups[$key]);
                    }
                }
            }
            $idFilter         = $this->filterBuilder->setField('category_id')
                ->setConditionType('eq')
                ->setValue($id)
                ->create();
            $visibilityFilter = $this->filterBuilder->setField('visibility')
                ->setConditionType('in')
                ->setValue('2,3,4')
                ->create();
            $this->filterGroupBuilder->addFilter($idFilter)->addFilter($visibilityFilter);
            $filterGroups[] = $this->filterGroupBuilder->create();
            $searchCriteria->setFilterGroups($filterGroups);

            $searchResult = $this->productRepository->getList($searchCriteria);
            $storeId      = $category->getStore()->getId();
            $useIdOrSku   = $this->getUseIdOrSku($type, $storeId);

            switch ($type) {
                case 'GTM':
                    $path        = $this->helperData->getBreadCrumbsPath();
                    $products    = [];
                    $productsGa4 = [];
                    $result      = [];
                    $resultGa4   = [];
                    $i           = 0;

                    /** @var Product $item */
                    foreach ($searchResult->getItems() as $item) {
                        $i++;
                        $products[$i]['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
                        $products[$i]['name']     = $item->getName();
                        $products[$i]['price']    = $this->helperData->getPrice($item);
                        $products[$i]['list']     = $category->getName();
                        $products[$i]['position'] = $i;
                        $products[$i]['category'] = $category->getName();

                        if ($this->helperData->isEnabledBrand($item, $storeId)) {
                            $products[$i]['brand'] = $this->helperData->getProductBrand($item);
                        }

                        if ($this->helperData->isEnabledVariant($item, $storeId)) {
                            $products[$i]['variant'] = $this->helperData->getColor($item);
                        }

                        $products[$i]['path']          = implode(' > ', $path) . ' > ' . $item->getName();
                        $products[$i]['category_path'] = implode(' > ', $path);
                        $result[]                      = $products[$i];

                        if ($this->helperData->isEnabledGTMGa4()) {
                            $productsGa4[$i]['item_id']        = $useIdOrSku ? $item->getSku() : $item->getId();
                            $productsGa4[$i]['item_name']      = $item->getName();
                            $productsGa4[$i]['price']          = $this->helperData->getPrice($item);
                            $productsGa4[$i]['item_list_name'] = $category->getName();
                            $productsGa4[$i]['item_list_id']   = $category->getId();
                            $productsGa4[$i]['index']          = $i;
                            $productsGa4[$i]['quantity']       = $this->helperData->getQtySale($item);

                            if ($this->helperData->isEnabledBrand($item, $storeId)) {
                                $productsGa4[$i]['item_brand'] = $this->helperData->getProductBrand($item);
                            }

                            if ($this->helperData->isEnabledVariant($item, $storeId)) {
                                $productsGa4[$i]['item_variant'] = $this->helperData->getColor($item);
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

                    $data['ecomm_pagetype'] = 'category';
                    $data['ecommerce']      = [
                        'currencyCode' => $this->helperData->getCurrentCurrency(),
                        'impressions'  => $result
                    ];

                    if ($this->helperData->isEnabledGTMGa4()) {
                        $data['ga4_event']          = 'view_item_list';
                        $data['ecommerce']['items'] = $resultGa4;
                    }

                    $data = $this->encodeJs($data);

                    break;
                case 'GA':
                    $products = [];
                    $i        = 0;

                    /** @var Product $item */
                    foreach ($searchResult->getItems() as $item) {
                        $products[] = $this->helperData->getItems($item, $category->getName(), ++$i);
                    }

                    $data = [
                        'event_name' => ['view_item_list'],
                        'data'       => [
                            'items' => $products,
                        ]
                    ];

                    break;
                case 'FbPixel':
                    $productIds = [];
                    $products   = [];
                    $value      = 0;

                    /** @var Product $item */
                    foreach ($searchResult->getItems() as $item) {
                        $productIds[]        = $useIdOrSku ? $item->getSku() : $item->getId();
                        $value               += $this->helperData->getPrice($item);
                        $product             = [];
                        $product['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
                        $product['name']     = $item->getName();
                        $product['quantity'] = $this->helperData->getQtySale($item);
                        $product['price']    = $this->helperData->getPrice($item);
                        $products[]          = $product;
                    }

                    $data = [
                        'track_type' => 'ViewContent',
                        'data'       => [
                            'content_ids'  => $productIds,
                            'content_name' => $category->getName(),
                            'content_type' => 'product',
                            'contents'     => $products,
                            'currency'     => $this->helperData->getCurrentCurrency(),
                            'value'        => $value
                        ]
                    ];

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return array|string
     */
    protected function getProductView($type, $id)
    {
        try {
            /** @var Product $currentProduct */
            try {
                $currentProduct = $this->productRepository->getById($id);
            } catch (Exception $e) {
                $currentProduct = $this->productRepository->get($id);
            }

            if ((int) $currentProduct->getStatus() === Status::STATUS_DISABLED) {
                return [];
            }

            switch ($type) {
                case 'GTM':
                    $data = $this->encodeJs($this->helperData->getProductDetailData($currentProduct));

                    break;
                case 'GA':
                    $products      = $this->helperData->getViewProductData($currentProduct);
                    $related       = $this->helperData->getRelatedProductData($currentProduct);
                    $upSell        = $this->helperData->getUpSellProductData($currentProduct);
                    $trackPosition = explode(
                        ',',
                        $this->helperData->getConfigAnalytics('track_position', $this->helperData->getStoreId())
                    );
                    $data = [
                        'event_name' => ['view_item'],
                        'data'       => [
                            'items' => [$products],
                        ]
                    ];
                    if (count($related) && in_array(self::RELATED, $trackPosition)) {
                        $data['related'] = [
                            'item_list_id'   => 'related_products',
                            'item_list_name' => 'Related products',
                            'items'          => $related,
                        ];
                    }
                    if (count($upSell) && in_array(self::UPSELL, $trackPosition)) {
                        $data['up_sell'] = [
                            'item_list_id'   => 'up_sell_products',
                            'item_list_name' => 'Up-sell products',
                            'items'          => $upSell,
                        ];
                    }

                    break;
                case 'FbPixel':
                    $fbData = $this->helperData->getFBProductView($currentProduct);
                    $data   = [
                        'track_type' => 'ViewContent',
                        'data'       => $fbData
                    ];

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param string|bool $step
     * @param string $option
     *
     * @return array|string
     */
    public function getCheckoutProductData($type, $id, $step, $option = 'Checkout')
    {
        try {
            if (!$this->isRest) {
                /** @var $quoteIdMask QuoteIdMask */
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($id, 'masked_id');
                /** @var Quote $quote */
                $quote = $this->quoteRepository->getActive($quoteIdMask->getQuoteId());
            } else {
                $quote = $this->quoteRepository->get($id);
            }

            $items = $quote->getAllVisibleItems();
            if (empty($items)) {
                return [];
            }

            switch ($type) {
                case 'GTM':
                    $products    = [];
                    $productsGa4 = [];
                    $i           = 1;

                    foreach ($items as $item) {
                        $products[] = $this->helperData->getProductCheckOutData($item);
                        if ($this->helperData->isEnabledGTMGa4() && $step === '2') {
                            $productGa4          = $this->helperData->getProductGa4CheckOutData($item);
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

                    if ($option === 'Shopping Cart') {
                        $data['ecomm_prodid']     = $eCommProdId;
                        $data['ecomm_pagetype']   = 'cart';
                        $data['ecomm_totalvalue'] = $quote->getGrandTotal();
                    }

                    if ($this->helperData->isEnabledGTMGa4() && $step === '2') {
                        $data['ga4_event']          = 'begin_checkout';
                        $data['ecommerce']['items'] = $productsGa4;
                    }

                    $data = $this->encodeJs($data);

                    break;
                case 'GA':
                    $products = [];

                    foreach ($items as $item) {
                        $products[] = $this->helperData->getCheckoutProductData($item);
                    }

                    $data = [
                        'event_name' => $step ? ['begin_checkout'] : ['checkout_progress'],
                        'data'       => [
                            'items'         => $products,
                            'coupon'        => $quote->getCouponCode() ?: '',
                            'checkout_step' => $step ? 1 : 2,
                        ],
                        'step'       => [
                            'event'           => 'set_checkout_option',
                            'checkout_step'   => $step ? 1 : 2,
                            'checkout_option' => $step ? 'begin_checkout' : 'checkout_progress'
                        ]
                    ];

                    break;
                case 'FbPixel':
                    $storeId    = $this->helperData->getStoreId();
                    $useIdOrSku = $this->getUseIdOrSku($storeId);
                    $value      = 0;
                    $products   = [];
                    $productIds = [];

                    /** @var QuoteItem $item */
                    foreach ($items as $item) {
                        $productIds[] = $useIdOrSku ? $item->getSku() : $item->getProductId();
                        $productInfo  = $this->helperData->getFBProductCheckOutData($item);
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
                            'currency'     => $this->helperData->getCurrentCurrency(),
                            'value'        => $value
                        ]
                    ];

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param string $action
     * @param string $id
     *
     * @return array
     */
    protected function getCheckoutSuccessData($type, $action, $id)
    {
        try {
            if ($action === 'multishippingcheckoutsuccess') {
                $orderIds       = explode(',', $id);
                $baseGrandTotal = 0;
                $data           = [];
                if ($orderIds) {
                    foreach ($orderIds as $orderId) {
                        if (!$this->isRest) {
                            $or = $this->order->loadByIncrementId($orderId);
                        } else {
                            /** @var Order $or */
                            $or = $this->orderRepository->get($orderId);
                        }

                        $baseGrandTotal              += $this->helperData->calculateTotals($or);
                        $data[$or->getIncrementId()] = $this->getCheckoutSuccessDataEachOrder($type, $or);
                    }
                }

                if ($this->helperData->isEnabledIgnoreOrders($this->helperData->getStoreId())
                    && $baseGrandTotal <= 0) {
                    $data = [];
                }
            } else {
                $order = $this->helperData->getSessionManager()->getLastRealOrder();

                if (!$order->getId()) {
                    if (!$this->isRest) {
                        $order = $this->order->loadByIncrementId($id);
                    } else {
                        $order = $this->orderRepository->get($id);
                    }
                }

                $data = $this->getCheckoutSuccessDataEachOrder($type, $order);

                if ($this->helperData->isEnabledIgnoreOrders($this->helperData->getStoreId())
                    && $this->helperData->calculateTotals($order) <= 0) {
                    $data = [];
                }

            }

            switch ($type) {
                case 'GTM':
                    $data = $this->encodeJs($data);

                    break;
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param Order $order
     *
     * @return array
     */
    protected function getCheckoutSuccessDataEachOrder($type, $order)
    {
        try {
            $items = $order->getItemsCollection([], true);

            switch ($type) {
                case 'GTM':
                    $products    = [];
                    $productsGa4 = [];
                    $skuItems    = [];
                    $skuItemsQty = [];

                    /** @var Item $item */
                    foreach ($items as $item) {
                        $productSku    = $item->getProduct()->getSku();
                        $products[]    = $this->helperData->getProductOrderedData($item);
                        $skuItems[]    = $productSku;
                        $skuItemsQty[] = $productSku . ':' . (int) $item->getQtyOrdered();
                        if ($this->helperData->isEnabledGTMGa4()) {
                            $productsGa4[] = $this->helperData->getGa4ProductOrderedData($item);
                        }
                    }

                    $eCommProdId = [];
                    foreach ($products as $product) {
                        $eCommProdId[] = $product['id'];
                    }

                    $data['ecomm_prodid']     = $eCommProdId;
                    $data['ecomm_pagetype']   = 'purchase';
                    $data['ecomm_totalvalue'] = $this->helperData->calculateTotals($order);
                    $createdAt                = $this->timezone->date(
                        new DateTime($order->getCreatedAt(), new DateTimeZone('UTC')),
                        $this->resolver->getLocale(),
                        true
                    );

                    $data['ecommerce'] = [
                        'purchase'     => [
                            'actionField' => [
                                'id'          => $order->getIncrementId(),
                                'affiliation' => $this->helperData->getAffiliationName(),
                                'order_id'    => $order->getIncrementId(),
                                'subtotal'    => $order->getSubtotal(),
                                'shipping'    => $order->getBaseShippingAmount(),
                                'tax'         => $order->getBaseTaxAmount(),
                                'revenue'     => $this->helperData->calculateTotals($order),
                                'discount'    => $order->getDiscountAmount(),
                                'coupon'      => (string) $order->getCouponCode(),
                                'created_at'  => $createdAt->format('Y-m-d H:i:s'),
                                'items'       => implode(';', $skuItems),
                                'items_qty'   => implode(';', $skuItemsQty)
                            ],
                            'products'    => $products
                        ],
                        'currencyCode' => $this->helperData->getCurrentCurrency()
                    ];

                    if ($this->helperData->isEnabledGTMGa4()) {
                        $data['ga4_event']                   = 'purchase';
                        $data['ecommerce']['transaction_id'] = $order->getIncrementId();
                        $data['ecommerce']['affiliation']    = $this->helperData->getAffiliationName();
                        $data['ecommerce']['value']          = $this->helperData->calculateTotals($order);
                        $data['ecommerce']['tax']            = $order->getBaseTaxAmount();
                        $data['ecommerce']['shipping']       = $order->getBaseShippingAmount();
                        $data['ecommerce']['currency']       = $this->helperData->getCurrentCurrency();
                        $data['ecommerce']['coupon']         = (string) $order->getCouponCode();
                        $data['ecommerce']['items']          = $productsGa4;
                    }

                    break;
                case 'GA':
                    $products = [];

                    /** @var Item $item */
                    foreach ($items as $item) {
                        $products[] = $this->helperData->getCheckoutProductData($item);
                    }

                    $data = [
                        'event_name' => ['purchase'],
                        'data'       => [
                            'transaction_id' => $order->getIncrementId(),
                            'value'          => $this->helperData->calculateTotals($order),
                            'currency'       => $this->helperData->getCurrentCurrency(),
                            'tax'            => $order->getBaseTaxAmount(),
                            'shipping'       => $order->getBaseShippingAmount(),
                            'items'          => $products
                        ]
                    ];

                    break;
                case 'FbPixel':
                    $products   = [];
                    $productIds = [];
                    $storeId    = $this->helperData->getStoreId();
                    $useIdOrSku = $this->getUseIdOrSku($storeId);

                    /** @var Item $item */
                    foreach ($items as $item) {
                        $productIds[] = $useIdOrSku ? $item->getSku() : $item->getProductId();
                        $products[]   = $this->helperData->getFBProductOrderedData($item);
                    }

                    $data = [
                        'track_type' => 'Purchase',
                        'data'       => [
                            'content_ids'  => $productIds,
                            'content_name' => 'Purchase',
                            'content_type' => 'product',
                            'contents'     => $products,
                            'currency'     => $this->helperData->getCurrentCurrency(),
                            'value'        => $this->helperData->calculateTotals($order)
                        ]
                    ];

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param string $type
     * @param array $products
     * @param string $categoryId
     *
     * @return string
     * @throws LocalizedException
     */
    public function getCodeCategoryDataGraphQl($type, $products, $categoryId)
    {
        $html = '';
        $data = $this->getCategoryDataGraphQl($type, $products, $categoryId);

        switch ($type) {
            case 'GTM':
                if (!$this->helperData->getConfigGTM('enabled')) {
                    throw new LocalizedException(__('Google Tag Manager is disabled'));
                }
                $tagId      = $this->helperData->getConfigGTM('tag_id');
                $canShowGtm = $this->helperData->isEnabled() && $this->helperData->getConfigGTM('enabled');

                if ($tagId && $canShowGtm && !empty($data)) {
                    $html = $this->getCodeGTM($tagId, $data, false);
                }

                return $html;
            case 'GA':
                if (!$this->helperData->getConfigAnalytics('enabled')) {
                    throw new LocalizedException(__('Google Analytics is disabled'));
                }
                $tagId            = $this->helperData->getConfigAnalytics('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigAnalytics('enabled');

                if ($tagId && $canShowAnalytics && !empty($data)) {
                    $html = $this->getCodeGA($data, false);
                }

                return $html;
            case 'FbPixel':
                if (!$this->helperData->getConfigPixel('enabled')) {
                    throw new LocalizedException(__('Facebook Pixed is disabled'));
                }
                $tagId            = $this->helperData->getConfigPixel('tag_id');
                $canShowAnalytics = $this->helperData->isEnabled() && $this->helperData->getConfigPixel('enabled');

                if ($tagId && $canShowAnalytics && !empty($data)) {
                    $html = $this->getCodeFbPixel($data, false);
                }

                return $html;
            default:
                throw new LocalizedException(__('The type is incorrect!'));
        }
    }

    /**
     * @param string $type
     * @param array $items
     * @param string $categoryId
     *
     * @return array|string
     */
    protected function getCategoryDataGraphQl($type, $items, $categoryId)
    {
        try {
            $category   = $this->category->load($categoryId);
            $storeId    = $category->getStore()->getId();
            $useIdOrSku = $this->getUseIdOrSku($type);

            switch ($type) {
                case 'GTM':
                    $path        = $this->helperData->getBreadCrumbsPath();
                    $products    = [];
                    $productsGa4 = [];
                    $result      = [];
                    $resultGa4   = [];
                    $i           = 0;

                    /** @var Product $item */
                    foreach ($items as $it) {
                        $i++;
                        $item                     = $this->productRepository->get($it['sku']);
                        $products[$i]['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
                        $products[$i]['name']     = $item->getName();
                        $products[$i]['price']    = $this->helperData->getPrice($item);
                        $products[$i]['list']     = $category->getName();
                        $products[$i]['position'] = $i;
                        $products[$i]['category'] = $category->getName();

                        if ($this->helperData->isEnabledBrand($item, $storeId)) {
                            $products[$i]['brand'] = $this->helperData->getProductBrand($item);
                        }

                        if ($this->helperData->isEnabledVariant($item, $storeId)) {
                            $products[$i]['variant'] = $this->helperData->getColor($item);
                        }

                        $products[$i]['path']          = implode(' > ', $path) . ' > ' . $item->getName();
                        $products[$i]['category_path'] = implode(' > ', $path);
                        $result[]                      = $products[$i];

                        if ($this->helperData->isEnabledGTMGa4()) {
                            $productsGa4[$i]['item_id']        = $useIdOrSku ? $item->getSku() : $item->getId();
                            $productsGa4[$i]['item_name']      = $item->getName();
                            $productsGa4[$i]['price']          = $this->helperData->getPrice($item);
                            $productsGa4[$i]['item_list_name'] = $category->getName();
                            $productsGa4[$i]['item_list_id']   = $category->getId();
                            $productsGa4[$i]['index']          = $i;
                            $productsGa4[$i]['quantity']       = $this->helperData->getQtySale($item);

                            if ($this->helperData->isEnabledBrand($item, $storeId)) {
                                $productsGa4[$i]['item_brand'] = $this->helperData->getProductBrand($item);
                            }

                            if ($this->helperData->isEnabledVariant($item, $storeId)) {
                                $productsGa4[$i]['item_variant'] = $this->helperData->getColor($item);
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

                    $data['ecomm_pagetype'] = 'category';
                    $data['ecommerce']      = [
                        'currencyCode' => $this->helperData->getCurrentCurrency(),
                        'impressions'  => $result
                    ];

                    if ($this->helperData->isEnabledGTMGa4()) {
                        $data['ga4_event']          = 'view_item_list';
                        $data['ecommerce']['items'] = $resultGa4;
                    }

                    $data = $this->encodeJs($data);

                    break;
                case 'GA':
                    $products = [];
                    $i        = 0;

                    /** @var Product $item */
                    foreach ($items as $it) {
                        $item       = $this->productRepository->get($it['sku']);
                        $products[] = $this->helperData->getItems($item, $category->getName(), ++$i);
                    }

                    $data = [
                        'event_name' => ['view_item_list'],
                        'data'       => [
                            'items' => $products,
                        ]
                    ];

                    break;
                case 'FbPixel':
                    $productIds = [];
                    $products   = [];
                    $value      = 0;

                    /** @var Product $item */
                    foreach ($items as $it) {
                        $item                = $this->productRepository->get($it['sku']);
                        $productIds[]        = $useIdOrSku ? $item->getSku() : $item->getId();
                        $value               += $this->helperData->getPrice($item);
                        $product             = [];
                        $product['id']       = $useIdOrSku ? $item->getSku() : $item->getId();
                        $product['name']     = $item->getName();
                        $product['quantity'] = $this->helperData->getQtySale($item);
                        $product['price']    = $this->helperData->getPrice($item);
                        $products[]          = $product;
                    }

                    $data = [
                        'track_type' => 'ViewContent',
                        'data'       => [
                            'content_ids'  => $productIds,
                            'content_name' => $category->getName(),
                            'content_type' => 'product',
                            'contents'     => $products,
                            'currency'     => $this->helperData->getCurrentCurrency(),
                            'value'        => $value
                        ]
                    ];

                    break;
                default:
                    $data = [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
}

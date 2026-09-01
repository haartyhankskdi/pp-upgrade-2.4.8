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

namespace Mageplaza\GoogleTagManager\Helper;

use Exception;
use IntlDateFormatter;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\CatalogPrice;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as CatalogEavAttribute;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Checkout\Model\Session;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Cookie\Helper\Cookie;
use Magento\Customer\Model\Attribute as CustomerEavAttribute;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Customer\Model\SessionFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;

/**
 * Class Data
 * @package Mageplaza\GoogleTagManager\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'googletagmanager';

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Registry
     */
    protected $_registry;

    /**
     * @var AttributeSetRepositoryInterface
     */
    protected $_attributeSet;

    /**
     * @var Category
     */
    protected $_resourceCategory;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var CatalogHelper
     */
    protected $_catalogHelper;

    /**
     * @var CatalogPrice
     */
    protected $_catalogPrice;

    /**
     * @var PriceCurrencyInterface
     */
    protected $_convert;

    /**
     * @var StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SessionFactory
     */
    protected $customerSession;

    /**
     * @var TimezoneInterface
     */
    protected $timeZone;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    /**
     * @var array
     */
    protected $productAndCustomerAttributes = [];

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param Registry $registry
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param Category $resourceCategory
     * @param ProductFactory $productFactory
     * @param CatalogHelper $catalogHelper
     * @param CatalogPrice $catalogPrice
     * @param Session $checkoutSession
     * @param PriceCurrencyInterface $priceCurrency
     * @param StockItemRepository $stockItemRepository
     * @param Cookie $cookieHelper
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SessionFactory $customerSession
     * @param TimezoneInterface $timeZone
     * @param CustomerGroup $customerGroup
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        Registry $registry,
        AttributeSetRepositoryInterface $attributeSetRepository,
        Category $resourceCategory,
        ProductFactory $productFactory,
        CatalogHelper $catalogHelper,
        CatalogPrice $catalogPrice,
        Session $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        StockItemRepository $stockItemRepository,
        Cookie $cookieHelper,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SessionFactory $customerSession,
        TimezoneInterface $timeZone,
        CustomerGroup $customerGroup
    ) {
        $this->_categoryFactory      = $categoryFactory;
        $this->_registry             = $registry;
        $this->_checkoutSession      = $checkoutSession;
        $this->_attributeSet         = $attributeSetRepository;
        $this->_resourceCategory     = $resourceCategory;
        $this->_productFactory       = $productFactory;
        $this->_catalogHelper        = $catalogHelper;
        $this->_catalogPrice         = $catalogPrice;
        $this->_convert              = $priceCurrency;
        $this->stockItemRepository   = $stockItemRepository;
        $this->cookieHelper          = $cookieHelper;
        $this->attributeRepository   = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession       = $customerSession;
        $this->timeZone              = $timeZone;
        $this->customerGroup         = $customerGroup;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param int $price
     *
     * @return float
     */
    public function convertPrice($price)
    {
        return $this->_convert->convert($price);
    }

    /**
     * @return Registry
     */
    public function getGtmRegistry()
    {
        return $this->_registry;
    }

    /**
     * Get GTM checkout session
     *
     * @return Session
     */
    public function getSessionManager()
    {
        return $this->_checkoutSession;
    }

    /**
     * Get Google Tag Manager Config
     *
     * @param string $code
     * @param null $store
     *
     * @return array|mixed
     */
    public function getConfigGTM($code, $store = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/googletag' . $code, $store);
    }

    /**
     * Get Google Analytics Config
     *
     * @param string $code
     * @param null $store
     *
     * @return mixed
     */
    public function getConfigAnalytics($code, $store = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/analyticstag' . $code, $store);
    }

    /**
     * @param string $code
     * @param null $store
     *
     * @return mixed
     */
    public function getConfigPixel($code, $store = null)
    {
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/pixeltag' . $code, $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getGTMUseIdOrSku($store = null)
    {
        return $this->getConfigGTM('use_id_or_sku', $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getPixelUseIdOrSku($store = null)
    {
        return $this->getConfigPixel('use_id_or_sku', $store);
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getAnalyticsUseIdOrSku($store = null)
    {
        return $this->getConfigAnalytics('use_id_or_sku', $store);
    }

    /**
     * Get Store Currency Code. EG:  'currencyCode': 'EUR','USD'
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getCurrentCurrency()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get Store ID
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Measure the additional of a product to a shopping cart.
     *
     * @param Product $product
     * @param int $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGTMAddToCartData($product, $quantity)
    {
        $useIdOrSku           = $this->getGTMUseIdOrSku($this->getStoreId());
        $productData          = [];
        $productData['id']    = $useIdOrSku ? $product->getSku() : $product->getId();
        $productData['sku']   = $product->getSku();
        $productData['name']  = $product->getName();
        $productData['price'] = $this->convertPrice($product->getQuoteItemPrice())
            ?: $this->convertPrice($product->getFinalPrice());

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $productData['brand'] = $this->getProductBrand($product);
        }
        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $productData['variant'] = $this->getColor($product);
        }
        if (!empty($this->getCategoryNameByProduct($product))) {
            $productData['category'] = $this->getCategoryNameByProduct($product);
        }

        $productData['quantity'] = $quantity;

        return $productData;
    }

    /**
     * @param Product $product
     * @param float $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGTMGa4AddToCartData($product, $quantity)
    {
        $useIdOrSku = $this->getGTMUseIdOrSku($this->getStoreId());

        $productData              = [];
        $productData['item_id']   = $useIdOrSku ? $product->getSku() : $product->getId();
        $productData['item_name'] = $product->getName();
        $productData['price']     = $this->convertPrice($product->getQuoteItemPrice())
            ?: $this->convertPrice($product->getFinalPrice());
        $productData['quantity']  = $quantity;

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $productData['item_brand'] = $this->getProductBrand($product);
        }
        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $productData['item_variant'] = $this->getColor($product);
        }
        if (!empty($this->getCategoryNameByProduct($product))) {
            $categoryPath = explode('/', $this->getCategoryNameByProduct($product));
            if (!empty($categoryPath)) {
                $j = null;
                foreach ($categoryPath as $cat) {
                    $key               = 'item_category' . $j;
                    $j                 = (int) $j;
                    $productData[$key] = $cat;
                    $j++;
                }
            }
        }

        return $productData;
    }

    /**
     * @param Product $product
     * @param string $list
     * @param int $position
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getItems($product, $list, $position)
    {
        $useIdOrSku = $this->getAnalyticsUseIdOrSku($this->getStoreId());
        $data       = [
            'id'            => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'          => $product->getName(),
            'list_name'     => $list,
            'category'      => $this->getCategoryNameByProduct($product),
            'list_position' => $position,
            'quantity'      => $this->getQtySale($product),
            'price'         => $this->getPrice($product),
        ];

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $data['variant'] = $this->getColor($product);
        }

        return $data;
    }

    /**
     * @param Product $product
     * @param float $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBAddToCartData($product, $quantity)
    {
        $useIdOrSku = $this->getPixelUseIdOrSku($this->getStoreId());

        return [
            'id'       => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'     => $product->getName(),
            'price'    => $this->convertPrice($product->getQuoteItemPrice())
                ?: $this->convertPrice($product->getFinalPrice()),
            'quantity' => $quantity
        ];
    }

    /**
     * @param Product $product
     * @param int $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBAddToWishlistData($product, $quantity)
    {
        $useIdOrSku = $this->getPixelUseIdOrSku($this->getStoreId());

        return [
            'id'       => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'     => $product->getName(),
            'price'    => $product->getFinalPrice(),
            'quantity' => $quantity
        ];
    }

    /**
     * @param Product $product
     * @param float $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGAAddToCartData($product, $quantity)
    {
        $useIdOrSku = $this->getAnalyticsUseIdOrSku($this->getStoreId());
        $data       = [
            'id'        => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'      => $product->getName(),
            'list_name' => 'Add To Cart',
            'category'  => $this->getCategoryNameByProduct($product),
            'quantity'  => $quantity,
            'price'     => $this->convertPrice($product->getQuoteItemPrice())
                ?: $this->convertPrice($product->getFinalPrice())
        ];

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $data['variant'] = $this->getColor($product);
        }

        return $data;
    }

    /**
     * @param Product $product
     *
     * @return string
     */
    public function getCategoryNameByProduct($product)
    {
        $categoryIds  = $product->getCategoryIds();
        $categoryName = '';
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $category     = $this->_categoryFactory->create()->load($categoryId);
                $categoryName .= '/' . $category->getName();
            }
        }

        return trim($categoryName, '/');
    }

    /**
     * @param Product $product
     * @param float $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGARemoveFromCartData($product, $quantity)
    {
        $useIdOrSku = $this->getAnalyticsUseIdOrSku($this->getStoreId());

        $data = [
            'items' => [
                [
                    'id'        => $useIdOrSku ? $product->getSku() : $product->getId(),
                    'name'      => $product->getName(),
                    'list_name' => 'Remove To Cart',
                    'category'  => $this->getCategoryNameByProduct($product),
                    'quantity'  => $quantity,
                    'price'     => $this->getPrice($product)
                ]
            ]
        ];

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $data['items'][0]['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $data['items'][0]['variant'] = $this->getColor($product);
        }

        return $data;
    }

    /**
     * @param Product|ProductInterface $product
     *
     * @return float
     */
    public function getPrice($product)
    {
        $price = $product->getPriceInfo() ?
            $product->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE) : $product->getPrice();

        if (is_object($price)) {
            $price = $price->getValue();
        }

        return $price;
    }

    /**
     * Measure the removal of a product from a shopping cart.
     *
     * @param Product $product
     * @param float $quantity
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGTMRemoveFromCartData($product, $quantity)
    {
        $useIdOrSku = $this->getGTMUseIdOrSku($this->getStoreId());

        $productData          = [];
        $productDataGa4       = [];
        $productData['id']    = $useIdOrSku ? $product->getSku() : $product->getId();
        $productData['sku']   = $product->getSku();
        $productData['name']  = $product->getName();
        $productData['price'] = $this->getPrice($product);
        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $productData['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $productData['variant'] = $this->getColor($product);
        }
        if (!empty($this->getCategoryNameByProduct($product))) {
            $productData['category'] = $this->getCategoryNameByProduct($product);
        }
        $productData['quantity'] = $quantity;

        if ($this->isEnabledGTMGa4()) {
            $productDataGa4['item_id']   = $useIdOrSku ? $product->getSku() : $product->getId();
            $productDataGa4['item_name'] = $product->getName();
            $productDataGa4['price']     = $this->getPrice($product);
            $productDataGa4['quantity']  = $quantity;
            if ($this->isEnabledBrand($product, $this->getStoreId())) {
                $productDataGa4['item_brand'] = $this->getProductBrand($product);
            }

            if ($this->isEnabledVariant($product, $this->getStoreId())) {
                $productDataGa4['item_variant'] = $this->getColor($product);
            }
            if (!empty($this->getCategoryNameByProduct($product))) {
                $categoryPath = explode('/', $this->getCategoryNameByProduct($product));
                if (!empty($categoryPath)) {
                    $j = null;
                    foreach ($categoryPath as $cat) {
                        $key                  = 'item_category' . $j;
                        $j                    = (int) $j;
                        $productDataGa4[$key] = $cat;
                        $j++;
                    }
                }
            }
        }

        $data = [
            'event'     => 'removeFromCart',
            'ecommerce' => [
                'currencyCode' => $this->getCurrentCurrency(),
                'remove'       => [
                    'products' => [$productData]
                ]
            ]
        ];

        if ($this->isEnabledGTMGa4()) {
            $data['ga4_event']          = 'remove_from_cart';
            $data['ecommerce']['items'] = [$productDataGa4];
        }

        return $data;
    }

    /**
     * Get data layered in product detail page
     *
     * @param Product $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductDetailData($product)
    {
        $categoryPath = '';
        $path         = $this->getBreadCrumbsPath();
        $useIdOrSku   = $this->getGTMUseIdOrSku($this->getStoreId());
        if (count($path) > 1) {
            array_pop($path);
            $categoryPath = implode(' > ', $path);
        }

        $productData        = [];
        $productDataGa4     = [];
        $productData['id']  = $useIdOrSku ? $product->getSku() : $product->getId();
        $productData['sku'] = $product->getSku();

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $productData['variant'] = $this->getColor($product);
        }

        $productData['name']  = $product->getName();
        $productData['price'] = $this->getPrice($product);
        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $productData['brand'] = $this->getProductBrand($product);
        }
        $productData['attribute_set_id']   = $product->getAttributeSetId();
        $productData['attribute_set_name'] = $this->_attributeSet
            ->get($product->getAttributeSetId())->getAttributeSetName();

        if ($product->getCategory()) {
            $productData['category'] = $product->getCategory()->getName();
        }

        if ($categoryPath) {
            $productData['category_path'] = $categoryPath;
        }

        $itemData = [
            'id'                       => $productData['id'],
            'google_business_vertical' => 'retail'
        ];

        $data = [

            'remarketing_event' => 'view_item',
            'value'             => $this->getPrice($product),
            'items'             => [$itemData],

            'ecommerce' => [
                'detail' => [
                    'actionField' => [
                        'list' => $product->getCategory() ? $product->getCategory()->getName() : 'Product View'
                    ],
                    'products'    => [$productData]
                ]
            ]
        ];

        if ($this->isEnabledGTMGa4()) {
            $productDataGa4['item_id']   = $useIdOrSku ? $product->getSku() : $product->getId();
            $productDataGa4['item_name'] = $product->getName();
            $productDataGa4['price']     = $this->getPrice($product);

            if ($this->isEnabledBrand($product, $this->getStoreId())) {
                $productDataGa4['item_brand'] = $this->getProductBrand($product);
            }

            if ($this->isEnabledVariant($product, $this->getStoreId())) {
                $productDataGa4['item_variant'] = $this->getColor($product);
            }

            if ($product->getCategory()) {
                $productDataGa4['item_list_name'] = $product->getCategory()->getName();
                $productDataGa4['item_list_id']   = $product->getCategory()->getId();
            }

            if (!empty($path)) {
                $i = null;
                foreach ($path as $cat) {
                    $key                  = 'item_category' . $i;
                    $i                    = (int) $i;
                    $productDataGa4[$key] = $cat;
                    $i++;
                }
            }

            $data['ga4_event']          = 'view_item';
            $data['ecommerce']['items'] = [$productDataGa4];
        }

        return $data;
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getViewProductData($product)
    {
        $useIdOrSku = $this->getAnalyticsUseIdOrSku($this->getStoreId());
        $data       = [
            'item_id'   => $useIdOrSku ? $product->getSku() : $product->getId(),
            'item_name' => $product->getName(),
            'list_name' => $product->getCategory() ? $product->getCategory()->getName() : 'Product View',
            'category'  => $this->getCategoryNameByProduct($product),
            'quantity'  => $this->getQtySale($product),
            'price'     => $this->getPrice($product)
        ];

        $customDimensions  = $this->getDimensions();
        $customMetrics     = $this->getMetrics();
        $dimensionsOptions = $customDimensions ? Data::jsonDecode($customDimensions) : [];
        $metricsOptions    = $customMetrics ? Data::jsonDecode($customMetrics) : [];

        $data = $this->setDataToDimensionsAndMetrics($data, $product, $dimensionsOptions, true);
        $data = $this->setDataToDimensionsAndMetrics($data, $product, $metricsOptions, false);

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $data['variant'] = $this->getColor($product);
        }

        return $data;
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getRelatedProductData($product)
    {
        $relatedProducts = $product->getRelatedProducts();
        $data            = [];
        $itemListId      = 'related_products';
        $itemListName    = 'Related Products';
        $data            = $this->getProductPositionData($relatedProducts, $data, $itemListId, $itemListName);

        return $data;
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getUpSellProductData($product)
    {
        $upSellProducts = $product->getUpSellProducts();
        $data           = [];
        $itemListId     = 'upsell_products';
        $itemListName   = 'Upsell Products';
        $data           = $this->getProductPositionData($upSellProducts, $data, $itemListId, $itemListName);

        return $data;
    }

    /**
     * @param $products
     * @param array $data
     * @param $itemListId
     * @param $itemListName
     * @return array|mixed
     * @throws NoSuchEntityException
     */
    public function getProductPositionData($products, $data, $itemListId, $itemListName)
    {
        if (!empty($products)) {
            foreach ($products as $product) {
                $product = $this->_productFactory->create()->load($product->getId());
                $useIdOrSku  = $this->getAnalyticsUseIdOrSku($this->getStoreId());
                $productData = [
                    'item_id'        => $useIdOrSku ? $product->getSku() : $product->getId(),
                    'item_name'      => $product->getName(),
                    'affiliation'    => '',
                    'item_category'  => $this->getCategoryNameByProduct($product),
                    'item_list_id'   => $itemListId,
                    'item_list_name' => $itemListName,
                    'quantity'       => $this->getQtySale($product),
                    'price'          => $this->getPrice($product)
                ];

                $customDimensions  = $this->getDimensions();
                $customMetrics     = $this->getMetrics();
                $dimensionsOptions = $customDimensions ? Data::jsonDecode($customDimensions) : [];
                $metricsOptions    = $customMetrics ? Data::jsonDecode($customMetrics) : [];

                $productData = $this->setDataToDimensionsAndMetrics($productData, $product, $dimensionsOptions, true);
                $productData = $this->setDataToDimensionsAndMetrics($productData, $product, $metricsOptions, false);

                if ($this->isEnabledBrand($product, $this->getStoreId())) {
                    $productData['item_brand'] = $this->getProductBrand($product);
                }

                if ($this->isEnabledVariant($product, $this->getStoreId())) {
                    $productData['item_variant'] = $this->getColor($product);
                }

                $data[] = $productData;
            }
        }

        return $data;
    }

    /**
     * @param Item $item
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCheckoutProductData($item)
    {
        $product    = $this->getProduct($item);
        $useIdOrSku = $this->getAnalyticsUseIdOrSku($this->getStoreId());
        $data       = [
            'id'        => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'      => $product->getName(),
            'list_name' => 'Cart View',
            'category'  => $this->getCategoryNameByProduct($product),
            'quantity'  => $item->getQtyOrdered() ?: $item->getQty(),
            'price'     => $this->convertPrice($item->getBasePrice())
        ];

        if ($this->isEnabledBrand($product, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($product);
        }

        if ($this->isEnabledVariant($product, $this->getStoreId())) {
            $data['variant'] = $this->getColor($product);
        }

        return $data;
    }

    /**
     * @param Product $product
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBProductView($product)
    {
        $useIdOrSku = $this->getPixelUseIdOrSku($this->getStoreId());
        $price      = $this->getPrice($product);

        return [
            'content_ids'  => [$useIdOrSku ? $product->getSku() : $product->getId()],
            'content_name' => $product->getName(),
            'content_type' => 'product',
            'contents'     => [
                [
                    'id'       => $useIdOrSku ? $product->getSku() : $product->getId(),
                    'name'     => $product->getName(),
                    'price'    => $price,
                    'quantity' => $this->getQtySale($product)
                ]
            ],
            'currency'     => $this->getCurrentCurrency(),
            'value'        => $price
        ];
    }

    /**
     * @param Item $item
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBProductCheckOutData($item)
    {
        $product    = $this->getProduct($item);
        $useIdOrSku = $this->getPixelUseIdOrSku($this->getStoreId());

        return [
            'id'       => $useIdOrSku ? $product->getSku() : $product->getId(),
            'name'     => $product->getName(),
            'price'    => $this->convertPrice($item->getBasePrice()),
            'quantity' => $item->getQty() ?: 1
        ];
    }

    /**
     * @param Item $item
     *
     * @return Product
     */
    public function getProduct($item)
    {
        if ($item->getProductType() === 'configurable') {
            $selectedProduct = $this->_productFactory->create();
            $selectedProduct->load($selectedProduct->getIdBySku($item->getSku()));
        } else {
            $selectedProduct = $item->getProduct();
        }

        return $selectedProduct;
    }

    /**
     * @param Item $item
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getProductCheckOutData($item)
    {
        $selectedProduct = $this->getProduct($item);
        $useIdOrSku      = $this->getGTMUseIdOrSku($this->getStoreId());

        $data = [
            'id'                 => $useIdOrSku ? $selectedProduct->getSku() : $selectedProduct->getId(),
            'name'               => $selectedProduct->getName(),
            'sku'                => $selectedProduct->getSku(),
            'price'              => $this->convertPrice($item->getBasePrice()),
            'quantity'           => $item->getQty(),
            'attribute_set_id'   => $selectedProduct->getAttributeSetId(),
            'attribute_set_name' => $this->_attributeSet
                ->get($selectedProduct->getAttributeSetId())->getAttributeSetName()
        ];

        if ($this->isEnabledVariant($selectedProduct, $this->getStoreId())) {
            $data['variant'] = $this->getColor($selectedProduct);
        }

        if ($this->isEnabledBrand($selectedProduct, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($selectedProduct);
        }

        if (!empty($this->getCategoryNameByProduct($selectedProduct))) {
            $data['category'] = $this->getCategoryNameByProduct($selectedProduct);
        }

        return $data;
    }

    /**
     * @param Item $item
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductGa4CheckOutData($item)
    {
        $selectedProduct = $this->getProduct($item);
        $useIdOrSku      = $this->getGTMUseIdOrSku($this->getStoreId());

        $data = [
            'item_id'   => $useIdOrSku ? $selectedProduct->getSku() : $selectedProduct->getId(),
            'item_name' => $selectedProduct->getName(),
            'price'     => $this->convertPrice($item->getBasePrice()),
            'quantity'  => $item->getQty()
        ];

        if ($this->isEnabledVariant($selectedProduct, $this->getStoreId())) {
            $data['item_variant'] = $this->getColor($selectedProduct);
        }

        if ($this->isEnabledBrand($selectedProduct, $this->getStoreId())) {
            $data['item_brand'] = $this->getProductBrand($selectedProduct);
        }

        if (!empty($this->getCategoryNameByProduct($selectedProduct))) {
            $categoryPath = explode('/', $this->getCategoryNameByProduct($selectedProduct));
            if (!empty($categoryPath)) {
                $j = null;
                foreach ($categoryPath as $cat) {
                    $key        = 'item_category' . $j;
                    $j          = (int) $j;
                    $data[$key] = $cat;
                    $j++;
                }
            }
        }

        return $data;
    }

    /**
     * @param Item $item
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getProductOrderedData($item)
    {
        $selectedProduct = $this->getProduct($item);
        $useIdOrSku      = $this->getGTMUseIdOrSku($this->getStoreId());

        $data['id']    = $useIdOrSku ? $selectedProduct->getSku() : $selectedProduct->getId();
        $data['name']  = $selectedProduct->getName();
        $data['price'] = $this->convertPrice($item->getBasePrice());

        if ($this->isEnabledVariant($selectedProduct, $this->getStoreId())) {
            $data['variant'] = $this->getColor($selectedProduct);
        }

        if ($this->isEnabledBrand($selectedProduct, $this->getStoreId())) {
            $data['brand'] = $this->getProductBrand($selectedProduct);
        }

        if (!empty($this->getCategoryNameByProduct($selectedProduct))) {
            $data['category'] = $this->getCategoryNameByProduct($selectedProduct);
        }

        $data['attribute_set_id']   = $selectedProduct->getAttributeSetId();
        $data['attribute_set_name'] = $this->_attributeSet->get(
            $selectedProduct->getAttributeSetId()
        )->getAttributeSetName();
        $data['quantity']           = number_format($item->getQtyOrdered(), 0);

        return $data;
    }

    /**
     * @param Item $item
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getGa4ProductOrderedData($item)
    {
        $selectedProduct = $this->getProduct($item);
        $useIdOrSku      = $this->getGTMUseIdOrSku($this->getStoreId());

        $data['item_id']   = $useIdOrSku ? $selectedProduct->getSku() : $selectedProduct->getId();
        $data['item_name'] = $selectedProduct->getName();
        $data['price']     = $this->convertPrice($item->getBasePrice());
        $data['quantity']  = number_format($item->getQtyOrdered(), 0);

        if ($this->isEnabledVariant($selectedProduct, $this->getStoreId())) {
            $data['item_variant'] = $this->getColor($selectedProduct);
        }

        if ($this->isEnabledBrand($selectedProduct, $this->getStoreId())) {
            $data['item_brand'] = $this->getProductBrand($selectedProduct);
        }

        if (!empty($this->getCategoryNameByProduct($selectedProduct))) {
            $categoryPath = explode('/', $this->getCategoryNameByProduct($selectedProduct));
            if (!empty($categoryPath)) {
                $j = null;
                foreach ($categoryPath as $cat) {
                    $key        = 'item_category' . $j;
                    $j          = (int) $j;
                    $data[$key] = $cat;
                    $j++;
                }
            }
        }

        return $data;
    }

    /**
     * @param Item $item
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFBProductOrderedData($item)
    {
        $selectedProduct = $this->getProduct($item);
        $useIdOrSku      = $this->getPixelUseIdOrSku($this->getStoreId());

        $productData             = [];
        $productData['id']       = $useIdOrSku ? $selectedProduct->getSku() : $selectedProduct->getId();
        $productData['name']     = $selectedProduct->getName();
        $productData['price']    = $this->convertPrice($item->getBasePrice());
        $productData['quantity'] = number_format($item->getQtyOrdered(), 0);

        return $productData;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getAffiliationName()
    {
        $webName   = $this->storeManager->getWebsite()->getName();
        $groupName = $this->storeManager->getGroup()->getName();
        $storeName = $this->storeManager->getStore()->getName();

        return $webName . '-' . $groupName . '-' . $storeName;
    }

    /**
     * Check the following modules is installed
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public function moduleIsEnable($moduleName)
    {
        $result = false;
        if ($this->_moduleManager->isEnabled($moduleName)) {
            switch ($moduleName) {
                case 'Magento_Inventory':
                    $result = true;
                    break;
                case 'Mageplaza_Shopbybrand':
                    $result = true;
                    break;
                case 'Mageplaza_Osc':
                    $oscHelper = $this->objectManager->create(\Mageplaza\Osc\Helper\Data::class);
                    $result    = $oscHelper->isEnabled() ? true : false;
                    break;
                case 'Mageplaza_ThankYouPage':
                    $typHelper       = $this->objectManager->create(\Mageplaza\ThankYouPage\Helper\Data::class);
                    $rulesCollection = $this->objectManager->create(
                        \Mageplaza\ThankYouPage\Model\ResourceModel\Template\Collection::class
                    );
                    $rulesCollection->addFieldToFilter('page_type', 'order')->addFieldToFilter('status', 1);
                    $result = $typHelper->isEnabled()
                    && $typHelper->isOrderSuccessPageEnable()
                    && $rulesCollection->getSize() ? true : false;
                    break;
            }
        }

        return $result;
    }

    /**
     * Get product brand if module Mageplaza_Shopbybrand is installed
     *
     * @param Product $product
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getProductBrand($product)
    {
        $brand    = 'Default';
        $attrCode = $this->getConfigGeneral('brand_attribute', $this->getStoreId());

        $product = $this->_productFactory->create()->setStoreId($this->getStoreId())
            ->load($product->getId());

        if ($attrCode && $product->getAttributeText($attrCode)) {
            $brand = $product->getAttributeText($attrCode);
        }

        if ($this->moduleIsEnable('Mageplaza_Shopbybrand') && !$attrCode) {
            $sbbHelper    = $this->objectManager->create(\Mageplaza\Shopbybrand\Helper\Data::class);
            $brandFactory = $this->objectManager->create(\Mageplaza\Shopbybrand\Model\BrandFactory::class);

            if ($sbbHelper->getConfigGeneral('enabled') && $sbbHelper->getAttributeCode()) {
                $attrCode = $sbbHelper->getAttributeCode();
                if ($brandFactory->create()->loadByOption($product->getData($attrCode))->getValue()) {
                    $brand = $brandFactory->create()->loadByOption($product->getData($attrCode))->getValue();
                }
            }
        }

        return $brand;
    }

    /**
     * Get color of configurable and simple product
     *
     * @param Product $product
     *
     * @return array|null|string
     */
    public function getColor($product)
    {
        $color = [];

        switch ($product->getTypeId()) {
            case 'configurable':
                $configurationAtt = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                foreach ($configurationAtt as $att) {
                    if ($att['label'] === 'Color') {
                        foreach ($att['values'] as $value) {
                            $color[] = $value['label'];
                        }
                        break;
                    }
                }
                $color = implode(',', $color);

                return $color;
            case 'simple':
                $table          = $this->objectManager->create(Table::class);
                $eavAttribute   = $this->objectManager->get(Attribute::class);
                $colorAttribute = $eavAttribute->load($eavAttribute->getIdByCode('catalog_product', 'color'));
                $allColor       = $table->setAttribute($colorAttribute)->getAllOptions(false);
                foreach ($allColor as $color) {
                    if ($color['value'] === $product->getData('color')) {
                        return $color['label'];
                    }
                }

                return null;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getBreadCrumbsPath()
    {
        $path        = [];
        $breadCrumbs = $this->_catalogHelper->getBreadcrumbPath();
        foreach ($breadCrumbs as $breadCrumb) {
            $path [] = $breadCrumb['label'];
        }

        return $path;
    }

    /**
     * @param Product $product
     * @param null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEnabledBrand($product, $storeId = null)
    {
        return $this->getConfigGeneral('enabled_brand', $storeId) && $this->getProductBrand($product);
    }

    /**
     * @param Product $product
     * @param null $storeId
     *
     * @return bool
     */
    public function isEnabledVariant($product, $storeId = null)
    {
        return $this->getConfigGeneral('enabled_variant', $storeId) && $this->getColor($product);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabledDeductTax($storeId = null)
    {
        return $this->getConfigGeneral('enabled_deduct_tax', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabledDeductShipping($storeId = null)
    {
        return $this->getConfigGeneral('enabled_deduct_shipping', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabledTaxDeductionShipping($storeId = null)
    {
        return $this->getConfigGeneral('enabled_tax_deduction_shipping', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function isEnabledIgnoreOrders($storeId = null)
    {
        return $this->getConfigGeneral('enabled_ignore_orders', $storeId);
    }

    /**
     * @param Order $order
     *
     * @return float|null
     * @throws NoSuchEntityException
     */
    public function calculateTotals($order)
    {
        /** @var Order $order */
        $amount = $order->getGrandTotal();

        if ($this->isEnabledDeductTax($this->getStoreId())) {
            $amount -= $order->getTaxAmount();
            if ($this->isEnabledDeductShipping()) {
                $shippingAmount = $order->getShippingAmount();
                if (!$this->isEnabledTaxDeductionShipping()) {
                    $amount += $order->getShippingTaxAmount();
                }
                $amount -= $shippingAmount;
            }
        } else {
            if ($this->isEnabledDeductShipping()) {
                $shippingAmount = $order->getShippingAmount();
                if ($this->isEnabledTaxDeductionShipping()) {
                    $shippingAmount += $order->getShippingTaxAmount();
                }
                $amount -= $shippingAmount;
            }
        }

        $amount = $amount >= 0 ? $amount : 0;

        return $amount;
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEnabledGTMGa4()
    {
        return $this->isEnabled($this->getStoreId()) && $this->getConfigGTM('enabled', $this->getStoreId())
            && $this->getConfigGTM('ga4/enabled_ga4', $this->getStoreId());
    }

    /**
     * @param Product|ProductInterface $product
     *
     * @return float|int
     */
    public function getQtySale($product)
    {
        try {
            $stock = $this->stockItemRepository->get($product->getId());

            if ($this->versionCompare('2.3.0') && $this->moduleIsEnable('Magento_Inventory')) {
                $totalQty = 0;

                $getSalableQuantityDataBySku = $this->createObject(
                    \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku::class
                );
                $getAssignedStock            = $this->createObject(
                    \Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite::class
                );

                $websiteCode     = $this->storeManager->getWebsite()->getCode();
                $assignedStockId = $getAssignedStock->execute($websiteCode);

                if ($product->getTypeId() === Configurable::TYPE_CODE) {
                    $typeInstance           = $product->getTypeInstance();
                    $childProductCollection = $typeInstance->getUsedProducts($product);
                    foreach ($childProductCollection as $childProduct) {
                        $qty = $getSalableQuantityDataBySku->execute($childProduct->getSku());
                        foreach ($qty as $value) {
                            if ($value['stock_id'] == $assignedStockId) {
                                $totalQty += isset($value['qty']) ? $value['qty'] : 0;
                            }
                        }
                    }
                } else {
                    $qty = $getSalableQuantityDataBySku->execute($product->getSku());
                    foreach ($qty as $value) {
                        if ($value['stock_id'] == $assignedStockId) {
                            $totalQty += isset($value['qty']) ? $value['qty'] : 0;
                        }
                    }
                }

                return $totalQty;
            }

            return $stock->getIsInStock() ? $stock->getQty() : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function isUserNotAllowSaveCookie()
    {
        return ($this->getConfigAnalytics('secure_cookies')
                && $this->cookieHelper->isCookieRestrictionModeEnabled()
                && $this->_getRequest()->getCookie(Cookie::IS_USER_ALLOWED_SAVE_COOKIE, false))
            || !$this->getConfigAnalytics('secure_cookies');
    }

    /**
     * @return mixed
     */
    public function getDimensions()
    {
        return $this->getConfigAnalytics('custom_dimensions');
    }

    /**
     * @return mixed
     */
    public function getMetrics()
    {
        return $this->getConfigAnalytics('custom_metrics');
    }

    /**
     * @param null $column
     * @param null $value
     * @param bool $isFilter
     *
     * @return array
     */
    public function getProductAndCustomerAttributes($column = null, $value = null, $isFilter = false)
    {
        if (!$this->productAndCustomerAttributes) {
            $attributes = [];
            if ($column && $value) {
                $searchCriteria = $this->searchCriteriaBuilder->addFilter($column, $value)->create();
            } else {
                $searchCriteria = $this->searchCriteriaBuilder->create();
            }
            $productAttributes  = $this->attributeRepository->getList(
                'catalog_product',
                $searchCriteria
            );
            $customerAttributes = $this->attributeRepository->getList(
                'customer',
                $searchCriteria
            );

            /** @var CatalogEavAttribute $item */
            foreach ($productAttributes->getItems() as $item) {
                if ($item->getAttributeCode() !== 'tier_price') {
                    if ($isFilter) {
                        $attributes[] = 'product.' . $item->getAttributeCode();
                    } else {
                        $attributes['product'][] = [
                            'value' => 'product.' . $item->getAttributeCode(),
                            'label' => $item->getFrontendLabel(),
                        ];
                    }
                }
            }

            /** @var CustomerEavAttribute $item */
            foreach ($customerAttributes->getItems() as $item) {
                if ($isFilter) {
                    $attributes[] = 'customer.' . $item->getAttributeCode();
                } else {
                    $attributes['customer'][] = [
                        'value' => 'customer.' . $item->getAttributeCode(),
                        'label' => $item->getFrontendLabel(),
                    ];
                }
            }

            $this->productAndCustomerAttributes = $attributes;
        }

        return $this->productAndCustomerAttributes;
    }

    /**
     * @param array $data
     * @param Product $product
     * @param array $options
     * @param bool $isDimensions
     *
     * @return mixed
     */
    public function setDataToDimensionsAndMetrics($data, $product, $options, $isDimensions)
    {
        if (isset($options['option']['value']) && $options['option']['value']) {
            foreach ($options['option']['value'] as $option) {
                if (strpos($option['value'], 'product') !== false) {
                    $key = 'metric' . $option['index'];
                    if ($isDimensions) {
                        $key = 'dimension' . $option['index'];
                    }
                    if ($this->getValueDimensionsMetrics($option['value'], $product)) {
                        $data[$key] = $this->getValueDimensionsMetrics($option['value'], $product);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param string $attribute
     * @param null $product
     *
     * @return string
     */
    public function getValueDimensionsMetrics($attribute, $product = null)
    {
        $value                = '';
        $attr                 = explode('.', $attribute)[1];
        $attributesIsDateTime = $this->getProductAndCustomerAttributes('frontend_input', 'date', true);
        if (strpos($attribute, 'product') !== false) {
            if (!$product) {
                $product = $this->_registry->registry('product');
            }
            if ($product) {
                $value       = $product->getData($attr);
                $priceFields = ['price', 'cost'];

                if (in_array($attr, $priceFields, true) || strpos($attr, 'price') !== false) {
                    if ($attr === 'price') {
                        return number_format($this->getPrice($product), 2);
                    }

                    return number_format($this->convertPrice($value), 2);
                }

                if ($attr === 'category_ids') {
                    $values = [];
                    foreach ($value as $categoryId) {
                        $category = $this->_categoryFactory->create()->load($categoryId);
                        $values[] = $category->getName();
                    }

                    return implode(',', $values);
                }

                $attributeText = $product->getResource()->getAttribute($attr)
                    ->setStoreId($product->getStoreId())->getFrontend()->getValue($product);

                if (is_array($attributeText)) {
                    $attributeText = implode(',', $attributeText);
                }

                if ($attributeText) {
                    return $attributeText;
                }
            }
        }

        if (strpos($attribute, 'customer') !== false) {
            $customerSession = $this->customerSession->create();
            $customerGroupId = 0;
            if ($customerSession->isLoggedIn()) {
                $customer = $customerSession->getCustomer();
                $value    = $customer->getData($attr);
                if ($customer) {
                    $value           = $customer->getData($attr);
                    $customerGroupId = $customer->getGroupId();

                    if ($attr === 'gender') {
                        return $customer->getAttribute($attr)->getSource()->getOptionText($customer->getData($attr));
                    }
                }
            }

            if ($attr === 'group_id') {
                $customerGroups = $this->customerGroup->toOptionArray();

                return isset($customerGroups[$customerGroupId]['label'])
                    ? $customerGroups[$customerGroupId]['label'] : '';
            }
        }

        if (in_array($attribute, $attributesIsDateTime, true) && $value) {
            return date('Y/m/d H:i:s', strtotime($this->formatDate($value)));
        }

        return $value;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    public function formatDate($date)
    {
        return $this->timeZone->formatDate($date, IntlDateFormatter::MEDIUM, true);
    }
}

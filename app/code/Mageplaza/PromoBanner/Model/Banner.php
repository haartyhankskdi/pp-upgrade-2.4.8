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
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\Combine as CatalogRuleCombine;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory as ProductCombineFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;
use Magento\SalesRule\Model\Rule\Condition\Combine as SaleRuleCombine;
use Magento\SalesRule\Model\Rule\Condition\CombineFactory as SalesCombineFactory;
use Mageplaza\PromoBanner\Model\Config\Source\Position;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModelBanner;

/**
 * Class Banner
 *
 * @package Mageplaza\PromoBanner\Model
 *
 * @method string getName()
 * @method Banner setName(string $value)
 * @method int getStatus()
 * @method Banner setStatus(int $value)
 * @method string getStoreIds()
 * @method string getCustomerGroupIds()
 * @method string getType()
 * @method Banner setType(string $value)
 * @method string getContent()
 * @method Banner setContent(string $value)
 * @method string getCmsBlockId()
 * @method Banner setCmsBlockId(int $value)
 * @method string getBannerImage()
 * @method Banner setBannerImage(string $value)
 * @method string getSliderImages()
 * @method Banner setSliderImages(string $value)
 * @method string getPopupImage()
 * @method Banner setPopupImage(string $value)
 * @method string getPopupResponsive()
 * @method Banner setPopupResponsive(string $value)
 * @method string getFloatingImage()
 * @method Banner setFloatingImage(string $value)
 * @method string getUrl()
 * @method Banner setUrl(string $value)
 * @method string getPosition()
 * @method Banner setPosition(string $value)
 * @method int getPage()
 * @method Banner setPage(int $value)
 * @method string getPageType()
 * @method Banner setPageType(string $value)
 * @method int getShowProductPage()
 * @method Banner setShowProductPage(int $value)
 * @method string getAutoCloseTime()
 * @method Banner setAutoCloseTime(string $value)
 * @method string getAutoReopenTime()
 * @method Banner setAutoReopenTime(string $value)
 */
class Banner extends AbstractModel
{
    /**
     * @var ProductCombineFactory
     */
    protected $_productCombineFactory;

    /**
     * @var SalesCombineFactory
     */
    protected $_salesCombineFactory;

    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $_productIds;

    /**
     * Store matched product Ids with banner id
     *
     * @var array
     */
    protected $dataProductIds;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var Iterator
     */
    protected $_resourceIterator;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ResourceModelBanner
     */
    protected $resourceModel;

    /**
     * Banner constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param ProductCombineFactory $productCombineFactory
     * @param SalesCombineFactory $salesCombineFactory
     * @param CollectionFactory $productCollectionFactory
     * @param Iterator $resourceIterator
     * @param ProductFactory $productFactory
     * @param ResourceModelBanner $resourceModel
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        ProductCombineFactory $productCombineFactory,
        SalesCombineFactory $salesCombineFactory,
        CollectionFactory $productCollectionFactory,
        Iterator $resourceIterator,
        ProductFactory $productFactory,
        ResourceModelBanner $resourceModel,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_productCombineFactory    = $productCombineFactory;
        $this->_salesCombineFactory      = $salesCombineFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_resourceIterator         = $resourceIterator;
        $this->_productFactory           = $productFactory;
        $this->resourceModel             = $resourceModel;

        parent::__construct($context, $registry, $formFactory, $localeDate, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModelBanner::class);
        $this->setIdFieldName('banner_id');
    }

    /**
     * Get condition combine model instance
     *
     * @return CatalogRuleCombine|SaleRuleCombine
     */
    public function getConditionsInstance()
    {
        return $this->_salesCombineFactory->create();
    }

    /**
     * Get product condition  combine model instance
     *
     * @return CatalogRuleCombine
     */
    public function getActionsInstance()
    {
        return $this->_productCombineFactory->create();
    }

    /**
     * @param string $formName
     *
     * @return string
     */
    public function getConditionsFieldSetId($formName = '')
    {
        return $formName . 'banner_conditions_fieldset_' . $this->getId();
    }

    /**
     * @return AbstractModel
     */
    public function afterSave()
    {
        $position = $this->getPosition();
        if (($position === Position::PAGE_TOP
                || $position === Position::CONTENT_TOP
                || $position === Position::UNDER_ADD_TO_CART_BUTTON
                || $position === Position::POPUP
                || $position === Position::RIGHT_FLOATING
                || $position === Position::LEFT_FLOATING
            )
            && $this->getPage()
            && $this->getShowProductPage()
        ) {
            $this->reindex();
        }

        return parent::afterSave();
    }

    /**
     * @return $this
     */
    public function reindex()
    {
        $this->getMatchingProductIds();
        $this->resourceModel->deleteActionIndex($this->getId());
        if (!empty($this->dataProductIds) && is_array($this->dataProductIds)) {
            $this->resourceModel->insertActionIndex($this->dataProductIds);
        }

        return $this;
    }

    /**
     * Get array of product ids which are matched by rule
     *
     * @return array|null
     */
    public function getMatchingProductIds()
    {
        if ($this->_productIds === null) {
            $this->_productIds = [];
            $this->setCollectedAttributes([]);

            $productCollection = $this->getProductCollection();
            $this->getActions()->collectValidatedAttributes($productCollection);

            $this->_resourceIterator->walk(
                $productCollection->getSelect(),
                [[$this, 'callbackValidateProduct']],
                [
                    'attributes' => $this->getCollectedAttributes(),
                    'product'    => $this->_productFactory->create()
                ]
            );
        }

        return $this->_productIds;
    }

    /**
     * @return ProductCollection
     */
    protected function getProductCollection()
    {
        /** @var $productCollection ProductCollection */
        $productCollection = $this->_productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*')
            ->setVisibility(
                [
                    Visibility::VISIBILITY_IN_CATALOG,
                    Visibility::VISIBILITY_BOTH
                ]
            )
            ->addAttributeToFilter('status', 1);

        return $productCollection;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     *
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        $bannerId = $this->getId();
        if ($bannerId && $this->getActions()->validate($product)) {
            $this->_productIds[]    = $product->getId();
            $this->dataProductIds[] = ['banner_id' => $bannerId, 'product_id' => $product->getId()];
        }
    }
}

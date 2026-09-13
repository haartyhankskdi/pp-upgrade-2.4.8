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
use Mageplaza\PromoBanner\Api\Data\PromoBannerInterface;
use Mageplaza\PromoBanner\Model\Config\Source\Position;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModelBanner;

/**
 * Class Banner
 *
 * @package Mageplaza\PromoBanner\Model
 */
class Banner extends AbstractModel implements PromoBannerInterface
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
        $this->_productCombineFactory = $productCombineFactory;
        $this->_salesCombineFactory = $salesCombineFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_resourceIterator = $resourceIterator;
        $this->_productFactory = $productFactory;
        $this->resourceModel = $resourceModel;

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
                    'product' => $this->_productFactory->create()
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
            $this->_productIds[] = $product->getId();
            $this->dataProductIds[] = ['banner_id' => $bannerId, 'product_id' => $product->getId()];
        }
    }

    /**
     * @inheritDoc
     */
    public function getBannerId()
    {
        return $this->getData(PromoBannerInterface::BANNER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setBannerId($value)
    {
        return $this->setData(PromoBannerInterface::BANNER_ID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData(PromoBannerInterface::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($value)
    {
        return $this->setData(PromoBannerInterface::NAME, $value);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(PromoBannerInterface::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($value)
    {
        return $this->setData(PromoBannerInterface::STATUS, $value);
    }

    /**
     * @inheritDoc
     */
    public function getStoreIds()
    {
        return $this->getData(PromoBannerInterface::STORE_IDS);
    }

    /**
     * @inheritDoc
     */
    public function setStoreIds($value)
    {
        return $this->setData(PromoBannerInterface::STORE_IDS, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerGroupIds()
    {
        return $this->getData(PromoBannerInterface::CUSTOMER_GROUP_IDS);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerGroupIds($value)
    {
        return $this->setData(PromoBannerInterface::CUSTOMER_GROUP_IDS, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCategory()
    {
        return $this->getData(PromoBannerInterface::CATEGORY);
    }

    /**
     * @inheritDoc
     */
    public function setCategory($value)
    {
        return $this->setData(PromoBannerInterface::CATEGORY, $value);
    }

    /**
     * @inheritDoc
     */
    public function getFromDate()
    {
        return $this->getData(PromoBannerInterface::FROM_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setFromDate($value)
    {
        return $this->setData(PromoBannerInterface::FROM_DATE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getToDate()
    {
        return $this->getData(PromoBannerInterface::TO_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setToDate($value)
    {
        return $this->setData(PromoBannerInterface::TO_DATE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return $this->getData(PromoBannerInterface::PRIORITY);
    }

    /**
     * @inheritDoc
     */
    public function setPriority($value)
    {
        return $this->setData(PromoBannerInterface::PRIORITY, $value);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(PromoBannerInterface::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($value)
    {
        return $this->setData(PromoBannerInterface::TYPE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getBannerImage()
    {
        return $this->getData(PromoBannerInterface::BANNER_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setBannerImage($value)
    {
        return $this->setData(PromoBannerInterface::BANNER_IMAGE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getSliderImages()
    {
        return $this->getData(PromoBannerInterface::SLIDER_IMAGES);
    }

    /**
     * @inheritDoc
     */
    public function setSliderImages($value)
    {
        return $this->setData(PromoBannerInterface::SLIDER_IMAGES, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCmsBlockId()
    {
        return $this->getData(PromoBannerInterface::CMS_BLOCK_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCmsBlockId($value)
    {
        return $this->setData(PromoBannerInterface::CMS_BLOCK_ID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getContent()
    {
        return $this->getData(PromoBannerInterface::CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setContent($value)
    {
        return $this->setData(PromoBannerInterface::CONTENT, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPopupImage()
    {
        return $this->getData(PromoBannerInterface::POPUP_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setPopupImage($value)
    {
        return $this->setData(PromoBannerInterface::POPUP_IMAGE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPopupResponsive()
    {
        return $this->getData(PromoBannerInterface::POPUP_RESPONSIVE);
    }

    /**
     * @inheritDoc
     */
    public function setPopupResponsive($value)
    {
        return $this->setData(PromoBannerInterface::POPUP_RESPONSIVE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getFloatingImage()
    {
        return $this->getData(PromoBannerInterface::FLOATING_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setFloatingImage($value)
    {
        return $this->setData(PromoBannerInterface::FLOATING_IMAGE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getUrl()
    {
        return $this->getData(PromoBannerInterface::URL);
    }

    /**
     * @inheritDoc
     */
    public function setUrl($value)
    {
        return $this->setData(PromoBannerInterface::URL, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPosition()
    {
        return $this->getData(PromoBannerInterface::POSITION);
    }

    /**
     * @inheritDoc
     */
    public function setPosition($value)
    {
        return $this->setData(PromoBannerInterface::POSITION, $value);
    }

    /**
     * @inheritDoc
     */
    public function getFloatingPosition()
    {
        return $this->getData(PromoBannerInterface::FLOATING_POSITION);
    }

    /**
     * @inheritDoc
     */
    public function setFloatingPosition($value)
    {
        return $this->setData(PromoBannerInterface::FLOATING_POSITION, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPage()
    {
        return (int)$this->getData(PromoBannerInterface::PAGE);
    }

    /**
     * @inheritDoc
     */
    public function setPage($value)
    {
        return $this->setData(PromoBannerInterface::PAGE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPageType()
    {
        return $this->getData(PromoBannerInterface::PAGE_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setPageType($value)
    {
        return $this->setData(PromoBannerInterface::PAGE_TYPE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getCategoryIds()
    {
        return $this->getData(PromoBannerInterface::CATEGORY_IDS);
    }

    /**
     * @inheritDoc
     */
    public function setCategoryIds($value)
    {
        return $this->setData(PromoBannerInterface::CATEGORY_IDS, $value);
    }

    /**
     * @inheritDoc
     */
    public function getShowProductPage()
    {
        return $this->getData(PromoBannerInterface::SHOW_PRODUCT_PAGE);
    }

    /**
     * @inheritDoc
     */
    public function setShowProductPage($value)
    {
        return $this->setData(PromoBannerInterface::SHOW_PRODUCT_PAGE, $value);
    }

    /**
     * @inheritDoc
     */
    public function getAutoCloseTime()
    {
        return $this->getData(PromoBannerInterface::AUTO_CLOSE_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setAutoCloseTime($value)
    {
        return $this->setData(PromoBannerInterface::AUTO_CLOSE_TIME, $value);
    }

    /**
     * @inheritDoc
     */
    public function getAutoReopenTime()
    {
        return $this->getData(PromoBannerInterface::AUTO_REOPEN_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setAutoReopenTime($value)
    {
        return $this->setData(PromoBannerInterface::AUTO_REOPEN_TIME, $value);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(PromoBannerInterface::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(PromoBannerInterface::CREATED_AT);
    }
}

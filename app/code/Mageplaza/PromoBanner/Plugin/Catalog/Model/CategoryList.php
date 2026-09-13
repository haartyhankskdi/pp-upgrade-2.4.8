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
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Plugin\Catalog\Model;

use Magento\Catalog\Api\Data\CategoryExtensionInterfaceFactory;
use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Catalog\Model\CategoryList as CoreCategoryList;
use Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterfaceFactory;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;

/**
 * Class CategoryList
 * @package Mageplaza\PromoBanner\Plugin\Catalog\Model
 */
class CategoryList
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CategoryExtensionInterfaceFactory
     */
    private $categoryExtensionFactory;

    /**
     * @var PromoBannerSearchResultsInterfaceFactory
     */
    private $bannerSearchResultsFactory;

    /**
     * CategoryList constructor.
     *
     * @param Data $helperData
     * @param CategoryExtensionInterfaceFactory $categoryExtensionFactory
     * @param PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
     */
    public function __construct(
        Data $helperData,
        CategoryExtensionInterfaceFactory $categoryExtensionFactory,
        PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
    ) {
        $this->helperData = $helperData;
        $this->categoryExtensionFactory = $categoryExtensionFactory;
        $this->bannerSearchResultsFactory = $bannerSearchResultsFactory;
    }

    /**
     * @param CoreCategoryList $subject
     * @param CategorySearchResultsInterface $result
     *
     * @return CategorySearchResultsInterface
     */
    public function afterGetList(CoreCategoryList $subject, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }

        /** @var Collection $collection */
        $collection = $this->helperData->getPromoBannerCollection();

        foreach ($result->getItems() as $item) {
            $extensionAttributes = $item->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->categoryExtensionFactory->create();
            }
            $cloneCollection = clone $collection;
            $categoryId = $item->getId();
            $cloneCollection->addFieldToFilter(['page', 'category_ids'], [['eq' => 0], ['finset' => $categoryId]]);
            if (!$cloneCollection->getSize()) {
                continue;
            }
            $items = [];
            foreach ($cloneCollection as $banner) {
                /** @var Banner $banner */
                $bannerType = $banner->getType();
                if ($bannerType !== Type::SLIDER) {
                    $banner->setContent($this->helperData->getBannerHtml($banner));
                }
                $this->helperData->processBannerData($banner);
                $items[] = $banner;
            }
            $searchResult = $this->bannerSearchResultsFactory->create();
            $searchResult->setItems($items);
            $extensionAttributes->setMpPromoBanners($searchResult);
        }

        return $result;
    }
}

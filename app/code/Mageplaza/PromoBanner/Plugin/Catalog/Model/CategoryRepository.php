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
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository as CoreCategoryRepository;
use Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterfaceFactory;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;

/**
 * Class CategoryRepository
 * @package Mageplaza\PromoBanner\Plugin\Catalog\Model
 */
class CategoryRepository
{
    /**
     * @var CategoryExtensionInterfaceFactory
     */
    private $categoryExtensionFactory;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var PromoBannerSearchResultsInterfaceFactory
     */
    private $bannerSearchResultsFactory;

    /**
     * CategoryRepository constructor.
     *
     * @param CategoryExtensionInterfaceFactory $categoryExtensionFactory
     * @param Data $helperData
     * @param PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
     */
    public function __construct(
        CategoryExtensionInterfaceFactory $categoryExtensionFactory,
        Data $helperData,
        PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
    ) {
        $this->categoryExtensionFactory = $categoryExtensionFactory;
        $this->helperData = $helperData;
        $this->bannerSearchResultsFactory = $bannerSearchResultsFactory;
    }

    /**
     * @param CoreCategoryRepository $subject
     * @param CategoryInterface $result
     *
     * @return mixed
     */
    public function afterGet(CoreCategoryRepository $subject, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }

        /** @var Collection $collection */
        $collection = $this->helperData->getPromoBannerCollection();

        $extensionAttributes = $result->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->categoryExtensionFactory->create();
        }
        $categoryId = $result->getId();
        $collection->addFieldToFilter(['page', 'category_ids'], [['eq' => 0], ['finset' => $categoryId]]);
        if (!$collection->getSize()) {
            return $result;
        }
        $items = [];
        foreach ($collection as $banner) {
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

        return $result;
    }
}

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

use Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\ProductRepository as CoreProductRepository;
use Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterfaceFactory;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;

/**
 * Class ProductRepository
 * @package Mageplaza\PromoBanner\Plugin\Catalog\Model
 */
class ProductRepository
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var ProductExtensionInterfaceFactory
     */
    private $productExtensionFactory;

    /**
     * @var PromoBannerSearchResultsInterfaceFactory
     */
    private $bannerSearchResultsFactory;

    /**
     * ProductRepository constructor.
     *
     * @param Data $helperData
     * @param ProductExtensionInterfaceFactory $productExtensionFactory
     * @param PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
     */
    public function __construct(
        Data $helperData,
        ProductExtensionInterfaceFactory $productExtensionFactory,
        PromoBannerSearchResultsInterfaceFactory $bannerSearchResultsFactory
    ) {
        $this->helperData = $helperData;
        $this->productExtensionFactory = $productExtensionFactory;
        $this->bannerSearchResultsFactory = $bannerSearchResultsFactory;
    }

    /**
     * @param CoreProductRepository $subject
     * @param ProductSearchResultsInterface $result
     *
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(CoreProductRepository $subject, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }
        /** @var Collection $collection */
        $collection = $this->helperData->getPromoBannerCollection();

        foreach ($result->getItems() as $item) {
            $productId = $item->getId();
            $cloneCollection = clone $collection;
            $cloneCollection->getCollectionByProductId($productId);
            if (!$cloneCollection->getSize()) {
                continue;
            }
            $extensionAttributes = $item->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->productExtensionFactory->create();
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

    /**
     * @param CoreProductRepository $subject
     * @param ProductInterface $result
     *
     * @return mixed
     */
    public function afterGet(CoreProductRepository $subject, $result)
    {
        if (!$this->helperData->isEnabled()) {
            return $result;
        }
        /** @var Collection $collection */
        $collection = $this->helperData->getPromoBannerCollection();
        $productId = $result->getId();
        $collection->getCollectionByProductId($productId);
        if (!$collection->getSize()) {
            return $result;
        }
        $extensionAttributes = $result->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->productExtensionFactory->create();
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

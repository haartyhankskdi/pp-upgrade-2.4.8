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

namespace Mageplaza\PromoBanner\Model\Config\Source;

use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection as BannerCollection;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class Widget
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Widget extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $bannerCollection;

    /**
     * Widget constructor.
     *
     * @param CollectionFactory $bannerCollection
     */
    public function __construct(CollectionFactory $bannerCollection)
    {
        $this->bannerCollection = $bannerCollection;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var BannerCollection $collection */
        $collection = $this->bannerCollection->create();
        $collection->addFieldToFilter('status', true)->addFieldToFilter('position', Position::WIDGET);
        $options = [];

        foreach ($collection as $banner) {
            $options[] = [
                'value' => $banner->getId(),
                'label' => __($banner->getName())
            ];
        }

        return $options;
    }
}

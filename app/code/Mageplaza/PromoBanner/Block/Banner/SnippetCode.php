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

namespace Mageplaza\PromoBanner\Block\Banner;

use Magento\Customer\Model\Context as CustomerContext;
use Mageplaza\PromoBanner\Block\PromoBanner;
use Mageplaza\PromoBanner\Model\Config\Source\Position;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;

/**
 * Class SnippetCode
 * @package Mageplaza\PromoBanner\Block\Banner
 */
class SnippetCode extends PromoBanner
{

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Mageplaza_PromoBanner::promobanner.phtml');
        $this->setData('position', Position::SNIPPET_CODE . uniqid('-', false));
    }

    /**
     * @return Collection|null
     */
    public function getPromoBannerCollection()
    {
        if (!$this->helperData->isEnabled()) {
            return null;
        }

        $bannerId      = $this->getData('banner_id');
        $customerGroup = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        /** @var Collection $collection */
        $collection = $this->bannerCollection->create();
        $collection->addActiveFilter($customerGroup, $this->getStoreId(), $this->date->date());
        $collection->addPositionToFilter(Position::SNIPPET_CODE);
        $collection->addFieldToFilter('banner_id', $bannerId);
        foreach ($collection as $item) {
            $bannerType = $item->getType();
            if ($bannerType !== Type::SLIDER) {
                $item->setContent($this->getBannerHtml($item));
            }
            $this->setAutoTime($item);
        }

        return $collection;
    }
}

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

namespace Mageplaza\PromoBanner\Block;

use Exception;
use Magento\Cms\Block\Block;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;
use Zend_Serializer_Exception;

/**
 * Class PromoBanner
 *
 * @package Mageplaza\Banner\Block
 */
class PromoBanner extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $bannerCollection;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var HelperImage
     */
    protected $helperImage;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;

    /**
     * PromoBanner constructor.
     *
     * @param Context $context
     * @param CollectionFactory $bannerCollection
     * @param Data $helperData
     * @param DateTime $date
     * @param HttpContext $httpContext
     * @param Registry $registry
     * @param HelperImage $helperImage
     * @param FilterProvider $filterProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $bannerCollection,
        Data $helperData,
        DateTime $date,
        HttpContext $httpContext,
        Registry $registry,
        HelperImage $helperImage,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->bannerCollection = $bannerCollection;
        $this->helperData       = $helperData;
        $this->date             = $date;
        $this->httpContext      = $httpContext;
        $this->registry         = $registry;
        $this->helperImage      = $helperImage;
        $this->filterProvider   = $filterProvider;

        parent::__construct($context, $data);
    }

    /**
     * @return Collection
     */
    public function getPromoBannerCollection()
    {
        $customerGroup = $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        /** @var Collection $collection */
        $collection = $this->bannerCollection->create();
        $collection->addActiveFilter($customerGroup, $this->helperData->getStoreId(), $this->date->date());

        $actionName = $this->getRequest()->getFullActionName();
        if ($actionName === 'catalog_product_view') {
            $productId = $this->registry->registry('current_product')->getId();
            $collection->getCollectionByProductId($productId);
        } elseif ($actionName === 'catalog_category_view') {
            $entityId = $this->registry->registry('current_category')->getId();
            $collection->addFieldToFilter(['page', 'category_ids'], [['eq' => 0], ['finset' => $entityId]]);
        } else {
            $collection->addFieldToFilter(['page', 'page_type'], [['eq' => 0], ['finset' => $actionName]]);
        }

        $collection = $collection->addPositionToFilter($this->getPosition());

        foreach ($collection as $item) {
            /** @var Banner $item */
            $bannerType = $item->getType();
            if ($bannerType !== Type::SLIDER) {
                $item->setContent($this->getBannerHtml($item));
            }
            $this->setAutoTime($item);
        }

        return $collection;
    }

    /**
     * @param Banner $banner
     *
     * @return Banner
     */
    public function setAutoTime(Banner $banner)
    {
        if ($banner->getAutoCloseTime() === 'use_config') {
            $banner->setAutoCloseTime($this->helperData->getAutoCloseTime());
        }
        if ($banner->getAutoReopenTime() === 'use_config') {
            $banner->setAutoReopenTime($this->helperData->getAutoOpenTime());
        }

        return $banner;
    }

    /**
     * @param Banner $banner
     *
     * @return mixed|null
     */
    public function getSliderItems(Banner $banner)
    {
        try {
            $data = $this->helperData->unserialize($banner->getSliderImages());
            usort($data, function ($a, $b) {
                return ($a['sort_order'] <= $b['sort_order']) ? -1 : 1;
            });

            return $data;
        } catch (Zend_Serializer_Exception $e) {
            return null;
        }
    }

    /**
     * @param Banner $banner
     *
     * @return mixed|string
     */
    public function getBannerHtml(Banner $banner)
    {
        $html = '';
        switch ($banner->getType()) {
            case Type::SINGLE_IMAGE:
                $src = $this->escapeUrl($this->getImageSrc($banner->getBannerImage()));
                if ($banner->getUrl()) {
                    $html = '<div class="mppromobanner-container">
                                <a class="mppromobanner-url" href="' . $this->escapeUrl($banner->getUrl()) . '"
                                target="_blank" rel="noopener noreferrer">
                                    <img class="mppromobanner-image img-responsive"
                                    src="' . $src . '" alt="' . $banner->getBannerImage() . '">
                                </a>
                            </div>';
                } else {
                    $html = '<div class="mppromobanner-container">
                                <img class="mppromobanner-image img-responsive"
                                src="' . $src . '" alt="' . $banner->getBannerImage() . '">
                            </div>';
                }
                break;
            case Type::HTML_TEXT:
                $html = $this->filterContent($banner->getContent());
                $html = '<div class="mppromobanner-container">' . $html . '</div>';
                break;
            case Type::CMS_BLOCK:
                try {
                    $html = $this->getLayout()->createBlock(Block::class)
                        ->setBlockId($banner->getCmsBlockId())
                        ->toHtml();
                    $html = '<div class="mppromobanner-container">' . $html . '</div>';
                } catch (Exception $e) {
                    $html = '';
                }
                break;
        }

        if (empty($html)) {
            return $html;
        }

        if ($this->helperData->showCloseButton()) {
            $closeBtn = '<div class="mppromobanner-close">
                <div class="mppromobanner-close-btn" title="Close"></div>
            </div>';
            $html     = '<div class="mppromobanner-banner-' . $banner->getId() . ' mppromobanner-banner-style">' . $closeBtn . $html . '</div>';
        } else {
            $html = '<div class="mppromobanner-banner-' . $banner->getId() . ' mppromobanner-banner-style">' . $html . '</div>';
        }

        return $html;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getImageSrc($value)
    {
        return $this->helperImage->getImageSrc($value);
    }

    /**
     * @return Data
     */
    public function getChangeTimeOut()
    {
        return $this->helperData->getChangeTimeOut();
    }

    /**
     * @return mixed
     */
    public function showNavs()
    {
        return $this->helperData->showNav();
    }

    /**
     * @return mixed
     */
    public function showCloseBtn()
    {
        return $this->helperData->showCloseButton();
    }

    /**
     * @param string $content
     *
     * @return mixed|string
     */
    public function filterContent($content)
    {
        try {
            $content = $this->filterProvider->getBlockFilter()->filter($content);

            return $content;
        } catch (Exception $e) {
            return '';
        }
    }
}

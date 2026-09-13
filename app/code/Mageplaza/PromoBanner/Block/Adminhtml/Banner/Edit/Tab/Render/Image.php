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

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;

/**
 * Class Image
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render
 */
class Image extends \Magento\Framework\Data\Form\Element\Image
{
    /**
     * @var HelperImage
     */
    protected $helperImage;

    /**
     * Image constructor.
     *
     * @param HelperImage $helperImage
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        HelperImage $helperImage,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        array $data
    ) {
        $this->helperImage = $helperImage;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $urlBuilder, $data);
    }

    /**
     * Get image preview url
     *
     * @return string
     */
    protected function _getUrl()
    {
        $url = '';
        if ($this->getValue()) {
            $url = $this->helperImage->getMediaPath($this->getValue(), HelperImage::TEMPLATE_MEDIA_TYPE_BANNER);
        }

        return $url;
    }
}

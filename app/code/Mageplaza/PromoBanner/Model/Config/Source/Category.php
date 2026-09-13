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

namespace Mageplaza\PromoBanner\Model\Config\Source;

use Mageplaza\PromoBanner\Helper\Data;

/**
 * Class Category
 *
 * @package Mageplaza\PromoBanner\Model\Config\Source
 */
class Category extends AbstractSource
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Category constructor.
     *
     * @param Data $helperData
     */
    public function __construct(Data $helperData)
    {
        $this->helperData = $helperData;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->helperData->getCategoryList();
    }
}

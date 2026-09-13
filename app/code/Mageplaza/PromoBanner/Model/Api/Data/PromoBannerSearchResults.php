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

namespace Mageplaza\PromoBanner\Model\Api\Data;

use Magento\Framework\DataObject;
use Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterface;

/**
 * Class PromoBannerSearchResults
 * @package Mageplaza\PromoBanner\Model\Api\Data
 */
class PromoBannerSearchResults extends DataObject implements PromoBannerSearchResultsInterface
{

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->getData('items');
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items = null)
    {
        $this->setData('items', $items);
    }
}

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
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Block\Tag;

/**
 * Class AnalyticsTagHead
 * @package Mageplaza\GoogleTagManager\Block\Tag
 */
class AnalyticsTagHead extends AnalyticsTag
{
    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return ['mp_gtm_analytics_head'];
    }
}

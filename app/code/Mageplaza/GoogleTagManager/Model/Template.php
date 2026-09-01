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

namespace Mageplaza\GoogleTagManager\Model;

use Magento\Framework\Model\AbstractModel;
use Mageplaza\GoogleTagManager\Model\ResourceModel\Template as ResourceTemplate;

/**
 * Class Template
 * @package Mageplaza\GoogleTagManager\Model
 */
class Template extends AbstractModel
{
    /**
     *
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceTemplate::class);
    }
}

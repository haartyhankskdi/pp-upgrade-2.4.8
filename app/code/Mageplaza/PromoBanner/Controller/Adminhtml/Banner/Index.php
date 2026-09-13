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

namespace Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

use Magento\Backend\Model\View\Result\Page;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

/**
 * Class Index
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class Index extends Banner
{
    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->initPage();

        $resultPage->getConfig()->getTitle()->prepend(__('Manage Promo Banners'));

        return $resultPage;
    }
}

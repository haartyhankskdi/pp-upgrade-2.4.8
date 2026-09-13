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

use Magento\Backend\Model\View\Result\Forward;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

/**
 * Class NewAction
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class NewAction extends Banner
{
    /**
     * @return Forward|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}

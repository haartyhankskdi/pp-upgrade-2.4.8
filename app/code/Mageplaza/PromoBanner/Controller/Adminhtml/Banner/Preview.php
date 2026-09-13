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

namespace Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

/**
 * Class Preview
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class Preview extends Banner
{
    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        try {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Promo Banner Preview'));
            $this->_view->renderLayout();
            $this->getResponse()->setHeader('Content-Security-Policy', "script-src 'none'");
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred. The banner can not be opened for preview.'));
            $this->_redirect('*/*/');
        }
    }
}

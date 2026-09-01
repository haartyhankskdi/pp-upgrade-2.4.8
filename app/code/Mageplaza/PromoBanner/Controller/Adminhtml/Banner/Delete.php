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

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

/**
 * Class Delete
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class Delete extends Banner
{
    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $bannerId       = $this->getRequest()->getParam('banner_id');
        try {
            /** @var \Mageplaza\PromoBanner\Model\Banner $banner */
            $banner = $this->bannerFactory->create();
            $this->resourceModel->load($banner, $bannerId)->delete($banner);
            $this->messageManager->addSuccessMessage(__('The banner has been deleted.'));
        } catch (Exception $e) {
            // display error message
            $this->messageManager->addErrorMessage($e->getMessage());
            // go back to edit form
            $resultRedirect->setPath('*/*/edit', ['banner_id' => $bannerId]);

            return $resultRedirect;
        }

        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}

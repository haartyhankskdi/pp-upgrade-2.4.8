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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;
use Mageplaza\PromoBanner\Model\Banner as BannerModel;

/**
 * Class Edit
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class Edit extends Banner
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageplaza_PromoBanner::edit';

    /**
     * @return Page|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $bannerId = $this->getRequest()->getParam('banner_id');
        /** @var BannerModel $model */
        $model = $this->bannerFactory->create();

        if ($bannerId) {
            $this->resourceModel->load($model, $bannerId);
            if ($model->getId() !== $bannerId) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));

                return $this->_redirect('*/*');
            }
        }

        // set entered data if was error when we do save
        $data = $this->_session->getData('mppromobanner_banner_data', true);
        if (!empty($data)) {
            $model->setData($data);
        }

        $this->_coreRegistry->register('mppromobanner_banner', $model);

        /** @var Page $resultPage */
        $resultPage = $this->initPage();
        $resultPage->getConfig()->getTitle()->prepend($bannerId ? $model->getName() : __('New Promo Banner'));

        return $resultPage;
    }
}

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
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;
use Mageplaza\PromoBanner\Controller\Adminhtml\Banner;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;
use Mageplaza\PromoBanner\Model\BannerFactory;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModel;
use Psr\Log\LoggerInterface;
use Zend_Filter_Input;

/**
 * Class Save
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class Save extends Banner
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var HelperImage
     */
    protected $helperImage;

    /**
     * @var Type
     */
    protected $bannerType;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param BannerFactory $bannerFactory
     * @param ResourceModel $resourceModel
     * @param Date $dateFilter
     * @param Data $helperData
     * @param LoggerInterface $logger
     * @param HelperImage $helperImage
     * @param Type $bannerType
     * @param DataPersistorInterface $dataPersistor
     */
    /**
     * Save constructor.
     *
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param BannerFactory $bannerFactory
     * @param ResourceModel $resourceModel
     * @param Date $dateFilter
     * @param Data $helperData
     * @param LoggerInterface $logger
     * @param HelperImage $helperImage
     * @param Type $bannerType
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        BannerFactory $bannerFactory,
        ResourceModel $resourceModel,
        Date $dateFilter,
        Data $helperData,
        LoggerInterface $logger,
        HelperImage $helperImage,
        Type $bannerType,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->helperImage   = $helperImage;
        $this->bannerType    = $bannerType;
        parent::__construct(
            $context,
            $resultForwardFactory,
            $resultPageFactory,
            $coreRegistry,
            $bannerFactory,
            $resourceModel,
            $dateFilter,
            $helperData,
            $logger
        );
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue('mppromobanner');
        if ($data) {
            $bannerId = $this->getRequest()->getParam('banner_id');
            /** @var $model \Mageplaza\PromoBanner\Model\Banner */
            $model        = $this->bannerFactory->create();
            $filterValues = ['from_date' => $this->_dateFilter];
            $inputFilter  = new Zend_Filter_Input($filterValues, [], $data);
            $data         = $inputFilter->getUnescaped();
            if ($bannerId) {
                $this->resourceModel->load($model, $bannerId);
                if ($bannerId !== $model->getId()) {
                    $this->messageManager->addErrorMessage(__('The wrong banner is specified.'));
                    $this->_redirect('*/*/edit', ['banner_id' => $model->getId()]);
                }
            }
            $validateResult = $model->validateData(new DataObject($data));
            if ($validateResult !== true) {
                foreach ($validateResult as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);
                }
                $this->_session->setPageData($data);
                $this->dataPersistor->set('mppromobanner_banner', $data);
                $this->_redirect('*/*/edit', ['banner_id' => $model->getId()]);

                return;
            }

            $rule = $this->getRequest()->getPostValue('rule');
            if ($rule) {
                if (isset($rule['conditions'])) {
                    $data['conditions'] = $rule['conditions'];
                }
                if (isset($rule['actions'])) {
                    $data['actions'] = $rule['actions'];
                }
                unset($rule);
            }
            if (isset($data['slider_images'])) {
                unset($data['slider_images']['__empty']);
            }

            try {
                if (!empty($data['slider_images']) && $data['type'] === Type::SLIDER) {
                    foreach ($data['slider_images'] as $id => $item) {
                        $name = 'slider_images' . $id . '_image';
                        $this->helperImage->uploadSliderImage(
                            $item,
                            $name,
                            $model->getImage()
                        );

                        $data['slider_images'][$id] = $item;
                    }
                } elseif ($data['type'] === Type::HTML_TEXT || $data['type'] === Type::CMS_BLOCK) {
                    unset($data['slider_images']);
                } else {
                    $this->helperImage->uploadBannerImage(
                        $data,
                        $data['type'],
                        $model->getImage()
                    );

                    unset($data['slider_images']);
                }

                foreach ($this->bannerType->toOptionArray() as $key => $value) {
                    if (isset($data[$value['value']]['value'])) {
                        $data[$value['value']] = $data[$value['value']]['value'];
                    }
                }

                $model->loadPost($data);
                $this->_session->setPageData($data);
                $this->dataPersistor->set('mppromobanner_banner', $data);

                $this->resourceModel->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the banner.'));
                $this->_session->setPageData(false);
                $this->dataPersistor->clear('mppromobanner_banner');

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['banner_id' => $model->getId()]);

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving the banner data. Please review the error log.')
                );
                $this->logger->critical($e->getMessage());
                $this->_session->setPageData($data);
                $this->dataPersistor->set('mppromobanner_banner', $data);
                if (empty($bannerId)) {
                    $this->_redirect('*/*/new');
                } else {
                    $this->_redirect('*/*/edit', ['banner_id' => $model->getId()]);
                }

                return;
            }
        }

        $this->_redirect('*/*/');
    }
}

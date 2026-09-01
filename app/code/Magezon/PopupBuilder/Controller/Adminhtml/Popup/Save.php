<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Controller\Adminhtml\Popup;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterInput;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magezon_PopupBuilder::popup_save';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    protected $dateFilter;

    /**
     * @var \Magezon\Core\Helper\Data
     */
    protected $coreHelper;

    /**
     * @param \Magento\Backend\App\Action\Context                   $context
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date        $dateFilter
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param \Magezon\Core\Helper\Data                             $coreHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        \Magezon\Core\Helper\Data $coreHelper
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->dateFilter   = $dateFilter;
        $this->coreHelper    = $coreHelper;
        parent::__construct($context);
    }

    /**
     * Sets image attribute data to false if image was removed
     *
     * @param array $data
     * @return array
     */
    public function preprocessing($data)
    {
        $fields = ['background_image'];
        foreach ($data as $k => &$row) {
            if (in_array($k, $fields)) {
                $row = $this->removeImageUrl($row);
            }

            if (is_array($row)) {
                $row = $this->preprocessing($row);
            }
        }

        return $data;
    }

    /**
     * Remove base url
     */
    public function removeImageUrl($string)
    {
        $mediaUrl = $this->coreHelper->getMediaUrl();
        return str_replace($mediaUrl, '', $string);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data         = $this->getRequest()->getPostValue();
        $redirectBack = $this->getRequest()->getParam('back', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (empty($data['popup_id'])) {
            unset($data['popup_id']);
        }

        if ($data) {
            if (isset($data['conditions']['salerule_id'])) {
                $data['salerule_id'] = $data['conditions']['salerule_id'];
                unset($data['conditions']['salerule_id']);
            } else {
                $data['salerule_id'] = [];
            }

            /** @var \Magezon\PopupBuilder\Model\Popup $model */
            $model = $this->_objectManager->create(\Magezon\PopupBuilder\Model\Popup::class);
            $id    = $this->getRequest()->getParam('popup_id');

            try {
                if (!isset($data['conditions'])) {
                    $data['conditions'] = [];
                }
                if (isset($data['conditions']['conditions'])) {
                    foreach ($data['conditions']['conditions'] as &$row) {
                        unset($row['record_id']);
                    }
                }

                $data['conditions'] = $this->coreHelper->serialize($data['conditions']);
                if (!isset($data['display_settings'])) {
                    $data['display_settings'] = [];
                }
                $data['display_settings'] = $this->coreHelper->serialize(
                    $this->preprocessing($data['display_settings'])
                );
                if (!isset($data['style_settings'])) {
                    $data['style_settings'] = [];
                }
                $data['style_settings'] = $this->coreHelper->serialize($this->preprocessing($data['style_settings']));
                unset($data['use_default']);
                $filterValues = [];
                if ($data['from_date']) {
                    $filterValues['from_date'] = $this->dateFilter;
                }
                if ($this->getRequest()->getParam('to_date')) {
                    $filterValues['to_date'] = $this->dateFilter;
                }
                if ($filterValues) {
                    $inputFilter = new FilterInput(
                        $filterValues,
                        [],
                        $data
                    );
                    $data = $inputFilter->getUnescaped();
                } else {
                    $data['from_date'] = $data['to_date'] = null;
                }

                $model->load($id);
                if ($id && !$model->getId()) {
                    throw new LocalizedException(__('This popup no longer exists.'));
                }
                $model->setData($data);
                $model->save();

                $this->messageManager->addSuccessMessage(__('You saved the popup.'));
                $this->dataPersistor->clear('current_popup');

                if ($redirectBack === 'save_and_new') {
                    return $resultRedirect->setPath('*/*/new');
                }

                if ($redirectBack === 'save_and_duplicate') {
                    $duplicate = $this->_objectManager->create(\Magezon\PopupBuilder\Model\Popup::class);
                    $duplicate->setData($model->getData());
                    $duplicate->setCreatedAt(null);
                    $duplicate->setUpdatedAt(null);
                    $duplicate->setId(null);
                    $duplicate->save();
                    $this->messageManager->addSuccessMessage(__('You duplicated the popup'));
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['popup_id' => $duplicate->getId(),
                            '_current' => true]
                    );
                }

                if ($redirectBack === 'save_and_close') {
                    return $resultRedirect->setPath('*/*/*');
                }

                return $resultRedirect->setPath('*/*/edit', ['popup_id' => $model->getId(), '_current' => true]);
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?:$e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the popup.'));
            }

            $this->dataPersistor->set('current_popup', $data);
            return $resultRedirect->setPath('*/*/edit', ['popup_id' => $this->getRequest()->getParam('popup_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}

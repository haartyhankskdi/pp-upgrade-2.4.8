<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes;

class Edit extends \Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes
{

    protected $resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('prescribernotes_id');
        $model = $this->_objectManager->create(\Nilesh\PrescriberNotes\Model\PrescriberNotes::class);
        
        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This Prescribernotes no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('nilesh_prescribernotes_prescribernotes', $model);
        
        // 3. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Prescribernotes') : __('New Prescribernotes'),
            $id ? __('Edit Prescribernotes') : __('New Prescribernotes')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Prescribernotess'));
        if(empty($model->getSubject()) && $model->getSubject() == ''){
            $title = "Prescribernotes ".$model->getId();
        }else{
            $title = $model->getSubject();
        }
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit %1', $title) : __('New Prescribernotes'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Nilesh_PrescriberNotes::view');
    }
}


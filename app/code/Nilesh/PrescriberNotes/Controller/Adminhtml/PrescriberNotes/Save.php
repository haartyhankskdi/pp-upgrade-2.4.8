<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Controller\Adminhtml\PrescriberNotes;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Nilesh_PrescriberNotes::prescribernotes'; 
    protected $dataPersistor;
    protected $imageUploader;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        //echo "<pre>";print_r($data);exit;        
        if ($data) {
            $id = $this->getRequest()->getParam('prescribernotes_id');

            $model = $this->_objectManager->create(\Nilesh\PrescriberNotes\Model\PrescriberNotes::class)->load($id);
            if (!$model->getPrescribernotesId() && $id) {
                $this->messageManager->addErrorMessage(__('This Prescribernotes no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }else{
                $authSession = $this->_objectManager->get('Magento\Backend\Model\Auth\Session')->getUser();
                $userName = $authSession->getFirstName() . " " . $authSession->getLastName();
                $data['created_by'] = $userName;
            }

            // if (isset($data['prescribernotes_upload'][0]['name']) && isset($data['prescribernotes_upload'][0]['tmp_name'])) {
            //     $data['prescribernotes_upload'] = $data['prescribernotes_upload'][0]['name'];            
            // } elseif (isset($data['prescribernotes_upload'][0]['image']) && !isset($data['prescribernotes_upload'][0]['tmp_name'])) {
            //     $data['prescribernotes_upload'] = $data['prescribernotes_upload'][0]['image'];
            // } else {
            //     $data['prescribernotes_upload'] = null;
            // } 

            // if (isset($data['prescribernotes_upload2'][0]['name']) && isset($data['prescribernotes_upload2'][0]['tmp_name'])) {
            //     $data['prescribernotes_upload2'] = $data['prescribernotes_upload2'][0]['name'];            
            // } elseif (isset($data['prescribernotes_upload2'][0]['image']) && !isset($data['prescribernotes_upload2'][0]['tmp_name'])) {
            //     $data['prescribernotes_upload2'] = $data['prescribernotes_upload2'][0]['image'];
            // } else {
            //     $data['prescribernotes_upload2'] = null;
            // } 

             $imageData = $data;
             //echo "<pre>";print_r($imageData);exit();
             foreach (['prescribernotes_upload', 'prescribernotes_upload2', 'prescribernotes_upload3','prescribernotes_upload4','prescribernotes_upload5'] as $key) {
                if (isset($imageData[$key]) && is_array($imageData[$key])) {
                    if (!empty($imageData[$key]['delete'])) {
                        //$model->setData($key, null);
                        $data[$key] = null;
                    } else {
                        if (isset($imageData[$key][0]['name']) && isset($imageData[$key][0]['tmp_name'])) {
                            $imageName = $imageData[$key][0]['name'];

                            $imageUploader = $this->_objectManager->get(
                                \Nilesh\PrescriberNotes\ImageUpload::class
                            );
                            $image = $imageUploader->moveFileFromTmp($imageName, true);

                            //$model->setData($key, $image);
                            $data[$key] = $imageName;

                        } else {
                            if (isset($imageData[$key][0]['name'])) {
                                //$model->setData($key, $imageData[$key][0]['name']);
                                $data[$key] = $imageData[$key][0]['name'];
                            }
                        }
                    }
                } else {
                    //$model->setData($key, null);
                    $data[$key] = null;
                }
            }

            //echo "<pre>";print_r($imageData);exit();
            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Prescribernotes.'));
                $this->dataPersistor->clear('nilesh_prescribernotes_prescribernotes');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['prescribernotes_id' => $model->getId()]);
                }
                //return $resultRedirect->setPath('*/*/');
                return $resultRedirect->setPath('customer/index/edit', ['id' => $data['connect_id']]);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Prescribernotes.'));
            }

            $this->dataPersistor->set('nilesh_prescribernotes_prescribernotes', $data);
            return $resultRedirect->setPath('*/*/edit', ['prescribernotes_id' => $this->getRequest()->getParam('prescribernotes_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Nilesh_PrescriberNotes::view');
    }
}


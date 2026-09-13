<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Forms;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\FormRegistry;
use Amasty\Label\Api\Data\LabelInterface;
use Amasty\Label\Model\LabelRegistry;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends \Amasty\Customform\Controller\Adminhtml\Form
{
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if ($id = $this->getRequest()->getParam('form_id', false)) {
            try {
                $form = $this->saveFormInfo((int) $id);
                $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
                $resultPage->getConfig()->getTitle()->prepend(
                    __('Edit Custom Form `%1`', $form->getTitle())
                );
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This form no longer exists...'));

                return $this->_redirect('*/*/index');
            }
        } else {
            $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
            $resultPage->getConfig()->getTitle()->prepend(__('New Custom Form'));
        }

        return $resultPage;
    }

    private function saveFormInfo(int $id): DataObject
    {
        $persistedData = $this->getPersistedData();
        $form = $this->formRepository->get($id);

        if (!empty($persistedData)) {
            $form->setData($persistedData);
            $this->formRegistry->register(FormRegistry::PERSISTED_DATA, $form);
        } else {
            $this->formRegistry->setCurrentForm($form);
        }

        return $form;
    }

    private function getPersistedData(): ?array
    {
        $session = $this->_getSession();

        return $session->hasAmCustomFormData() ? (array) $session->getAmCustomFormData() : null;
    }
}

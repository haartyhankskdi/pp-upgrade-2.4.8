<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Controller\Adminhtml\Answer;

use Amasty\Customform\Controller\Adminhtml\Answer;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Answer
{
    /**
     * @var string[]
     */
    protected $_publicActions = ['edit'];

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            if (!$id) {
                throw new NoSuchEntityException(__('Response was not found.'));
            }

            $model = $this->answerRepository->get($id);
        } catch (NoSuchEntityException $exception) {
            $this->messageManager->addErrorMessage(__('This Response no longer exists.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('amasty_customform/answer/index');
        }

        $this->formRegistry->setCurrentAnswer($model);
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Submitted Data #') . $model->getAnswerId());
        $resultPage->addBreadcrumb(__('Amasty: Custom Forms'), __('Submitted Data'));

        return $resultPage;
    }
}

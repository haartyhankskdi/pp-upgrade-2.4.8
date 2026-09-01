<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Profile;

use Magenest\SagePay\Controller\Adminhtml\Profile;

class View extends Profile
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->_coreRegistry->register('sagepay_profile_id', $id);
            /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
            $resultPage = $this->_initAction();
            $title = __('View Profile');
            $resultPage->getConfig()->getTitle()->prepend($title);

            return $resultPage;
        } else {
            $this->messageManager->addError(__('This profile no longer exists.'));

            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*/');
        }
    }
}

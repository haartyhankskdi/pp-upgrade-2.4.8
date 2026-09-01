<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Customer;

class Profile extends AbstractAction
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $block = $this->_view->getLayout()->getBlock('sagepay_customer_profile_list');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('My Subscription Profiles'));
        $this->_view->renderLayout();
    }
}

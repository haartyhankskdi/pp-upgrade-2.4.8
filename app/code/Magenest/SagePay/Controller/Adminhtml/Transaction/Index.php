<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;
use Magenest\SagePay\Controller\Adminhtml\Transaction;

class Index extends Transaction
{
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Transactions'));
        $transaction = $this->_transactionFactory->create()->getCollection();
        $transaction->addFieldToFilter("order_id", ['in' => ['NULL', 0]]);
        if ($transaction->getSize() > 0) {
            $this->messageManager->addWarningMessage(
                __("Warning: %1 Opayo transaction(s) have no order associated", $transaction->getSize())
            );
        }
        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_SagePay::transaction');
    }
}

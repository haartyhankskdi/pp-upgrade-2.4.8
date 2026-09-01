<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Transaction;

use Magento\Backend\App\Action;

class Delete extends \Magento\Backend\App\Action
{
    protected $transactionFactory;

    public function __construct(
        Action\Context $context,
        \Magenest\SagePay\Model\TransactionFactory $transactionFactory
    ) {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $transaction = $this->transactionFactory->create()->load($id);
                $transaction->delete();
                $this->messageManager->addSuccessMessage(__("The transaction has been deleted"));

                return $this->_redirect('sagepay/transaction/index');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __("Error: " . $e->getMessage()));
            }
        }
        $this->messageManager->addErrorMessage(__("Can't delete this transaction"));

        return $this->_redirect('sagepay/transaction/index');
    }
}

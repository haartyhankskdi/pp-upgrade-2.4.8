<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Transaction;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magenest\SagePay\Model\TransactionFactory;
use Psr\Log\LoggerInterface;

class MassDelete extends Action
{
    protected $_filter;

    protected $_transactionfactory;

    protected $_logger;

    public function __construct(
        Filter $filter,
        TransactionFactory $transactionFactory,
        Action\Context $context,
        LoggerInterface $loggerInterface
    ) {
        $this->_filter = $filter;
        $this->_transactionfactory = $transactionFactory;
        $this->_logger = $loggerInterface;
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_transactionfactory->create()->getCollection());
        $cardDeleted = 0;
        foreach ($collection->getItems() as $card) {
            $card->delete();
            $cardDeleted++;
        }
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $cardDeleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/');
    }
}

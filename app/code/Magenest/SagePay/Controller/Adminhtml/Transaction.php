<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magenest\SagePay\Model\TransactionFactory;
use Psr\Log\LoggerInterface;

abstract class Transaction extends Action
{
    protected $_transactionFactory;

    protected $_pageFactory;

    protected $_logger;

    protected $_coreRegistry;

    public function __construct(
        Action\Context $context,
        PageFactory $pageFactory,
        TransactionFactory $transactionFactory,
        LoggerInterface $loggerInterface,
        Registry $registry
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_logger = $loggerInterface;
        $this->_transactionFactory = $transactionFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $resultPage->setActiveMenu('Magenest_SagePay::transaction')
            ->addBreadcrumb(__('Transactions'), __('Transactions'));

        $resultPage->getConfig()->getTitle()->set(__('Transactions'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_SagePay::transaction');
    }
}

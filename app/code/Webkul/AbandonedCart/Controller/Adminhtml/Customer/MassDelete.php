<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AbandonedCart\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     **/
    protected $_scopeConfig;

    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Controller\Result\Forward
     **/
    protected $_resultForward;

    /**
     * @var \Webkul\AbandonedCart\Model\MailsLogFactory
     **/
    protected $_mailFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\Forward $resultForward
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Webkul\AbandonedCart\Model\MailsLogFactory $mailFactory
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\Forward $resultForward,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Webkul\AbandonedCart\Model\MailsLogFactory $mailFactory,
        Filter $filter
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->_resultForward = $resultForward;
        $this->_resultLayoutFactory = $resultLayoutFactory;
        $this->_mailFactory = $mailFactory;
        $this->filter = $filter;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $collection = $this->filter->getCollection($this->_mailFactory->create()->getCollection());
        $mailsDeleted = 0;
        foreach ($collection as $mails) {
            $this->deleteData($mails);
            $mailsDeleted++;
        }

        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $mailsDeleted)
        );

        return $resultRedirect->setPath('abandonedcart/customer/mailssent');
    }

    /**
     * Perform delete operation on models
     */
    public function deleteData($model)
    {
        $model->delete();
    }
}

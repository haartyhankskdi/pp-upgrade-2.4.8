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
use Magenest\SagePay\Model\ProfileFactory;
use Psr\Log\LoggerInterface;

abstract class Profile extends Action
{
    protected $_profileFactory;

    protected $_pageFactory;

    protected $_logger;

    protected $_coreRegistry;

    public function __construct(
        Action\Context $context,
        PageFactory $pageFactory,
        ProfileFactory $profileFactory,
        LoggerInterface $loggerInterface,
        Registry $registry
    ) {
        $this->_pageFactory = $pageFactory;
        $this->_logger = $loggerInterface;
        $this->_profileFactory = $profileFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    protected function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $resultPage->setActiveMenu('Magenest_SagePay::profile')
            ->addBreadcrumb(__('Subscription Profiles'), __('Subscription Profiles'));

        $resultPage->getConfig()->getTitle()->set(__('Subscription Profiles'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magenest_SagePay::profile');
    }
}

<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Customer;

use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session as CustomerSession;

class View extends Action
{
    protected $_resultPageFactory;

    protected $_logger;
    protected $_customerSession;

    protected $_coreRegistry;
    /**
     * @var Url
     */
    private $url;

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param LoggerInterface $loggerInterface
     * @param Registry $registry
     * @param Url $url
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        LoggerInterface $loggerInterface,
        Registry $registry,
        Url $url,
        CustomerSession $customerSession
    ) {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $pageFactory;
        $this->_logger = $loggerInterface;
        $this->_coreRegistry = $registry;
        $this->url = $url;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $this->_coreRegistry->register('sagepay_profile_customer_id', $id);

        $this->_view->loadLayout();
        $block = $this->_view->getLayout()->getBlock('sagepay_customer_profile_view');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('View Profile'));
        $this->_view->renderLayout();
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->url->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }
}

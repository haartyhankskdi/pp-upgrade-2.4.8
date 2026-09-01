<?php

namespace Magenest\SagePay\Controller\Customer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;

class AbstractAction extends Action
{
    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * Profile constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get(\Magento\Customer\Model\Url::class)->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
    }
}

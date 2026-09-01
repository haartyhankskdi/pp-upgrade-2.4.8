<?php
declare(strict_types=1);

namespace Kdi\ImageUpload\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Kdi\ImageUpload\Helper\CustomCookie;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\OrderFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var CustomCookie
     */
    private $customCookie;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerSession $customerSession
     * @param UrlInterface $urlInterface
     * @param SessionManagerInterface $sessionManager
     * @param CustomCookie $customCookie
     * @param ManagerInterface $messageManager
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerSession $customerSession,
        UrlInterface $urlInterface,
        SessionManagerInterface $sessionManager,
        CustomCookie $customCookie,
        ManagerInterface $messageManager,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->urlInterface = $urlInterface;
        $this->sessionManager = $sessionManager;
        $this->customCookie = $customCookie;
        $this->messageManager = $messageManager;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $this->logInfo("Received Order ID: " . print_r($orderId, true));
        
        if (!$orderId) {
            $this->messageManager->addErrorMessage(
                __("Order ID does not exist. Please use the URL from your email.")
            );
            return $this->redirect('/');
        }

        $this->customCookie->set($orderId);
        if (!$this->isCustomerLoggedIn()) {
            return $this->redirect('customer/account/login', [
                'referer' => base64_encode($this->_redirect->getRefererUrl())
            ]);
        }

        if (!$this->isOrderBelongsToCustomer($orderId)) {
            $this->messageManager->addErrorMessage(
                __("The order does not belong to the logged-in customer.")
            );
            return $this->redirect('/');
        }

        return $this->resultPageFactory->create();
    }

    /**
     * Log information to a custom log file.
     *
     * @param string $message
     * @return void
     */
    private function logInfo(string $message): void
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/index.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Redirect to a specified path
     *
     * @param string $path
     * @param array $params
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirect(string $path, array $params = [])
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($path, $params);
        return $resultRedirect;
    }

    /**
     * Check if customer is logged in.
     *
     * @return bool
     */
    private function isCustomerLoggedIn(): bool
    {
        return (bool)$this->customerSession->getCustomerId();
    }

    /**
     * Get order details by increment ID.
     *
     * @param string $orderId
     * @return \Magento\Sales\Model\Order|false
     */
    private function getOrderDetails(string $orderId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        return $order->getId() ? $order : false;
    }

    /**
     * Check if the order belongs to the logged-in customer.
     *
     * @param string $orderId
     * @return bool
     */
    private function isOrderBelongsToCustomer(string $orderId): bool
    {
        $customerId = $this->customerSession->getCustomerId();
        $order = $this->getOrderDetails($orderId);

        return $order && (int)$order->getCustomerId() === (int)$customerId;
    }
}

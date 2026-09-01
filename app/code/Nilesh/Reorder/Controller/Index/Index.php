<?php
declare(strict_types=1);

namespace Nilesh\Reorder\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $referer = $this->getRequest()->getParam('referer');
        // echo base64_decode($referer); exit();
        if($this->userLogin() == false){
            $this->_redirect($this->storeManager->getStore()->getUrl("customer/account/login", array("referer"=>base64_encode($this->storeManager->getStore()->getUrl("reorder/index/index", ['referer' => $referer])))));
        }else{
            $realUrl = base64_decode($referer);
            $this->_redirect($realUrl);
        }
        return $this->resultPageFactory->create();
    }

    /**
     * Check is it possible to reorder
     *
     * @param int $orderId
     * @return bool
     */
    public function userLogin()
    {
        if ($this->customerSession->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }
}

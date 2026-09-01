<?php
namespace Kdi\JumioVerification\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Kdi\JumioVerification\Block\Adminhtml\SmsButton;
use Kdi\JumioVerification\Block\Adminhtml\Customer\Edit\Tabs;
use Magento\Customer\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Kdi\JumioVerification\Model\JumioVerificationFactory;
use Magento\Email\Model\TemplateFactory;

class SendEmail extends Action
{
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $smsButton;
    protected $tabs;
    protected $_customerSession;
    protected $orderRepository;
    protected $_orderCollectionFactory;
    protected $jumioVerificationFactory;
    protected $templateFactory;

 
    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        SmsButton $smsButton,
        Tabs $tabs,
        JumioVerificationFactory $jumioVerificationFactory,
        TemplateFactory $templateFactory
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->smsButton = $smsButton;
        $this->tabs = $tabs;
        $this->_customerSession = $customerSession;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->jumioVerificationFactory = $jumioVerificationFactory;
        $this->templateFactory = $templateFactory;
    }

    public function execute()
    {
        $order = $observer->getData('order');
        $storeId = 1;
        $store = $this->_storeManager->getStore($storeId);
        $customerEmail = $order->getCustomerEmail();
        $response = $this->tabs->getInitiate();
        $res = json_decode($response, true);

        # get the href value
        $href = $res['web']['href'];
        $accId = $res['account']['id'];
        $workFlowId = $res['workflowExecution']['id'];die;

       
        // Save the data to the custom table
        $jumioVerificationModel = $this->jumioVerificationFactory->create();
        $jumioVerificationModel->setAccountId($accId);
        $jumioVerificationModel->setWorkflowId($workFlowId);
        $jumioVerificationModel->setStatus(0);
        $jumioVerificationModel->setCustomerEmail($customerEmail);
        $jumioVerificationModel->save();

        $orderCollection = $this->_orderCollectionFactory->create();
        $orderCollection->addFieldToFilter('customer_email', $customerEmail)
        ->setOrder('created_at', 'ASC');

        echo $count = $orderCollection->getSize();

        if ($count == 1) {
            $this->inlineTranslation->suspend();
            
            $templateId = 29;

            $templateVars = [
                'order' => $order,
                'verification_url' => $href,
            ];
            $sender = [
                'name' => $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE),
                'email' => $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE),
            ];

            $to = ['email' => $customerEmail];

            $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($to['email'])
                ->getTransport();
            $transport->sendMessage();

            $this->inlineTranslation->resume();
            echo 'email sent successfully.';
        }
    }

    // public function isCustomerFirstOrder()
    // {
    //     $customerId = $this->_customerSession->getCustomerId();
    //     echo $customerId;
    //     $orders = $this->_orderCollectionFactory->getCollection()
    //         ->addFieldToFilter('customer_id', $customerId)
    //         ->getFirstItem();
    //     echo "<pre>";
    //     print_r($orders->count());
    //     return false;
    // }
}

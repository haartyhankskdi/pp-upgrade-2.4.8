<?php

namespace Kdi\JumioVerification\Observer;
/**
 * SendOrderConfirmationEmail observer class
 * This observer is responsible for sending order confirmation emails to the customers
 * after an order is placed.
 */

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Event\Observer;
use Kdi\JumioVerification\Block\Adminhtml\SmsButton;
use Kdi\JumioVerification\Block\Adminhtml\Customer\Edit\Tabs;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Kdi\JumioVerification\Model\JumioVerificationFactory;

class SendOrderConfirmationEmail implements ObserverInterface
{
    /**
     * @var TransportBuilder $_transportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StateInterface $_inlineTranslation
     */
    protected $_inlineTranslation;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var SmsButton $smsButton
     */
    protected $smsButton;

    /**
     * @var Tabs $tabs
     */
    protected $tabs;

    /**
     * @var Session $customerSession
     */
    protected $_customerSession;

    /**
     * @var OrderCollectionFactory $_orderCollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var JumioVerificationFactory $jumioVerificationFactory
     */
    protected $jumioVerificationFactory;


    /**
     * SendOrderConfirmationEmail constructor.
     * 
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param SmsButton $smsButton
     * @param Tabs $tabs
     * @param Session $customerSession
     * @param OrderCollectionFactory $_orderCollectionFactory
     * @param JumioVerificationFactory $jumioVerificationFactory
     */
    
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        SmsButton $smsButton,
        Tabs $tabs,
        Session $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        JumioVerificationFactory $jumioVerificationFactory
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->smsButton = $smsButton;
        $this->tabs = $tabs;
        $this->_customerSession = $customerSession;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->jumioVerificationFactory = $jumioVerificationFactory;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getData("order");
        // $items = $order->getAllVisibleItems();
        // $storeId = $order->getStoreId();
        // $store = $this->_storeManager->getStore($storeId);
        // $customerEmail = $order->getCustomerEmail();
        // $customerName = $order->getcustomerName();
        // $response = $this->tabs->getInitiate();
        // $res = json_decode($response, true);

        // # get the href value
        // $href = $res["web"]["href"];
        // $accId = $res["account"]["id"];
        // $workFlowId = $res["workflowExecution"]["id"];

        // $orders = $this->_orderCollectionFactory
        //     ->create()
        //     ->addFieldToFilter("customer_email", $customerEmail)
        //     ->setOrder("created_at", "ASC");
        // $count = $orders->getSize();

        // foreach ($items as $item) {
        //     $product = $item->getProduct();
        //     $jumioStatus = $product->getData('jumio_status');
        // }
        // if ($count == 1 && $jumioStatus) {
        //     // Save the data to the custom table
        //     $jumioVerificationModel = $this->jumioVerificationFactory->create();
        //     $jumioVerificationModel->setAccountId($accId);
        //     $jumioVerificationModel->setWorkflowId($workFlowId);
        //     $jumioVerificationModel->setStatus(0);
        //     $jumioVerificationModel->setCustomerEmail($customerEmail);
        //     $jumioVerificationModel->setCustomerName($customerName);
        //     $jumioVerificationModel->save();

        //     $this->_inlineTranslation->suspend();
        //     $templateId = 29;
        //     $templateVars = [
        //         "order" => $order,
        //         "verification_url" => $href,
        //         "custName" => $customerName,
        //     ];
        //     $sender = [
        //         "name" => $this->scopeConfig->getValue(
        //             "trans_email/ident_general/name",
        //             ScopeInterface::SCOPE_STORE
        //         ),
        //         "email" => $this->scopeConfig->getValue(
        //             "trans_email/ident_general/email",
        //             ScopeInterface::SCOPE_STORE
        //         ),
        //     ];

        //     $to = ["email" => $customerEmail];

        //     $transport = $this->_transportBuilder
        //         ->setTemplateIdentifier($templateId)
        //         ->setTemplateOptions([
        //             "area" => "frontend",
        //             "store" => $storeId,
        //         ])
        //         ->setTemplateVars($templateVars)
        //         ->setFrom($sender)
        //         ->addTo($to["email"])
        //         ->getTransport();
        //     $transport->sendMessage();

        //     $this->_inlineTranslation->resume();
        // }
    }
}

<?php

namespace Magenest\SagePay\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Success extends Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sageHelper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var mixed
     */
    protected $quote;
    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
    /**
     * @var \Magenest\SagePay\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $_serialize;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    protected $_transactionCollectionFactory;

    /**
     * Success constructor.
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magenest\SagePay\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
    ) {
        parent::__construct($context);
        $this->_serialize = $serialize;
        $this->_checkoutSession = $checkoutSession;
        $this->sageHelper = $sageHelper;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->sageLogger = $sageLogger;
        $this->orderSender = $orderSender;
        $this->dataHelper = $dataHelper;
        $this->cartRepository = $cartRepository;
        $this->_transactionCollectionFactory = $transactionCollectionFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        return $this->_redirect("checkout/cart");
    }
}

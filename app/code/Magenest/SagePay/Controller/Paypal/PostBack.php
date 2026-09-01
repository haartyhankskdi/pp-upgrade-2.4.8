<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Paypal;

use Magenest\SagePay\Helper\Data;
use Magenest\SagepayLib\Classes\Constants;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magenest\SagepayLib\Classes\SagepayCommon;
use Magento\Framework\Exception\LocalizedException;

class PostBack extends Action
{

    /**
     * @var null
     */
    private $currentOrder = null;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Order\Payment\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magenest\SagepayLib\Classes\SagepaySettings|null
     */
    protected $sagePayConfig;

    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sageHelper;

    /**
     * @var Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderModel;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $orderResource;

    /**
     * PostBack constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory
     * @param \Magenest\SagePay\Model\SagePayDirect $sagePayDirect
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderModel
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Payment\TransactionFactory $transactionFactory,
        \Magenest\SagePay\Model\SagePayDirect $sagePayDirect,
        \Magento\Checkout\Model\Session $session,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Magento\Sales\Model\ResourceModel\Order $orderResource
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $session;
        $this->sagePayConfig = $sagePayDirect->getSagePayConfig();
        $this->sageHelper = $sageHelper;
        $this->orderSender = $orderSender;
        $this->_dataHelper = $dataHelper;
        $this->customerSession = $customerSession;
        $this->orderModel = $orderModel;
        $this->orderResource = $orderResource;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->setCustomerSession();
        if ($response = $this->processPayment()) {
            $quote = $this->checkoutSession->getQuote();
            $transactionData = $this->sageHelper->getResponseData($response, $quote, "Paypal");
            $transactionData['order_id'] = $this->checkoutSession->getLastRealOrder()->getId();
            $this->_eventManager->dispatch(
                "magenest_sagepay_save_transaction",
                ['transaction_data' => $transactionData]
            );
            if (($response['Status'] == Constants::SAGEPAY_REMOTE_STATUS_OK) ||
                ($response['Status'] == Constants::SAGEPAY_REMOTE_STATUS_REGISTERED)) {
                $this->completeOrder();
                return $this->_redirect('checkout/onepage/success');
            } else {
                $statusDetail = $response['StatusDetail'] ?? __("Payment Error: Unknown Error");
                $this->messageManager->addError($statusDetail);
                return $this->_redirect('checkout/cart');
            }
        } else {
            $errorMessage = filter_input(INPUT_POST, 'StatusDetail');
            $this->messageManager->addError(
                __("Payment Error: " . isset($errorMessage) ? $errorMessage : 'Unknown error!')
            );
            return $this->_redirect('checkout/cart');
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCustomerSession()
    {
        $order = $this->getOrder();
        if ($order->getId()) {
            $quoteId = $order->getData('quote_id');
            $orderEntityId = $order->getData('entity_id');
            $incrementId = $order->getData('increment_id');
            $customerId = $order->getData('customer_id');
            //Add data to the order session
            $this->checkoutSession->setLastSuccessQuoteId($quoteId);
            $this->checkoutSession->setLastQuoteId($quoteId);
            $this->checkoutSession->setLastOrderId($orderEntityId);
            $this->checkoutSession->setLastRealOrderId($incrementId);
            //Add data customer
            if ($customerId) {
                $this->customerSession->setIsLoggedIn('true');
                $this->customerSession->setCustomerId($customerId);
            }
        } else {
            throw new LocalizedException(__("Something went wrong. Please try again later."));
        }
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        if (!$this->currentOrder) {
            $transactionId = str_replace(["{", "}"], "", $this->getRequest()->getParam('VPSTxId'));
            $order = $this->orderModel->create();
            $this->orderResource->load($order, $transactionId, 'paypal_transaction_id');
            $this->currentOrder = $order;
        }
        return $this->currentOrder;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function completeOrder()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        if ($payment->getAdditionalInformation('is_authorize')) {
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);
            $amount = $this->_dataHelper->getPayAmount($order);
            if ($order && $amount) {
                $payment->authorize(true, $amount);
            }
        } else {
            $order = $payment->getOrder();
            $amount = $this->_dataHelper->getPayAmount($order);
            if ($order && $amount && $payment->canCapture()) {
                $payment->capture();
            }
        }
        $this->orderSender->send($order);
        $this->orderRepository->save($order);
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processPayment()
    {
        if ($this->getRequest()->getParam('vtx') &&
            $this->getRequest()->getParam('Status') == Constants::SAGEPAY_REMOTE_STATUS_PAYPAL_OK
        ) {
            return $this->getTransactionDetail();
        }
        return false;
    }

    /**
     * @return array|false|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTransactionDetail()
    {
        $order = $this->getOrder();
        $data = [
            'VPSProtocol' => $this->sagePayConfig->getProtocolVersion(),
            'TxType' => Constants::SAGEPAY_TXN_COMPLETE,
            'VPSTxId' => $this->getRequest()->getParam('VPSTxId'),
            'Amount' => number_format($this->_dataHelper->convertAmount($order), 2),
            'Accept' => $this->getRequest()->getParam('Status') == Constants::SAGEPAY_REMOTE_STATUS_PAYPAL_OK ?
                'YES' : 'NO'
        ];

        $result = SagepayCommon::requestPost($this->sagePayConfig->getPurchaseUrl('paypal'), $data);
        $result['VendorTxCode'] = $this->getRequest()->getParam('vtx');
        return array_merge(filter_input_array(INPUT_POST), $result);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancelOrder()
    {
        $order = $this->getOrder();
        $order->cancel();
        $this->orderRepository->save($order);
        $this->checkoutSession->restoreQuote();
    }
}

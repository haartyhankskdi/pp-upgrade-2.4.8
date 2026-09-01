<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magenest\SagePay\Helper\Data;
use Magenest\SagePay\Model\TransactionFactory;

class RedirectBack extends Action
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var TransactionFactory
     */
    protected $_transFactory;

    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sageHelper;

    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var
     */
    protected $orderObject;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    protected $_serialize;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $sendPaymentFailedEmail;

    /**
     * RedirectBack constructor.
     * @param Context $context
     * @param Data $data
     * @param TransactionFactory $transactionFactory
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serialize
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Checkout\Helper\Data $sendPaymentFailedEmail
     */
    public function __construct(
        Context $context,
        Data $data,
        TransactionFactory $transactionFactory,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Helper\Data $sendPaymentFailedEmail
    ) {
        $this->_serialize = $serialize;
        $this->_helper = $data;
        $this->_transFactory = $transactionFactory;
        $this->sageHelper = $sageHelper;
        $this->sageLogger = $sageLogger;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->sendPaymentFailedEmail = $sendPaymentFailedEmail;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $params = $this->getRequest()->getParams();
            $cres = $this->getRequest()->getParam('cres');
            $pares = $this->getRequest()->getParam('PaRes');
            $order = $this->getOrder();
            $payment = $order->getPayment();
            if (!$payment) {
                throw new LocalizedException(
                    __('Could Not Found Payment Transaction')
                );
            }
            //handle for duplicate response
            if ($payment->getAdditionalInformation('is_sagepay_processing_success')) {
                return $this->redirectSuccessPage($order);
            }
            $this->_debug($params);
            $url = $this->_helper->getPiEndpointUrl();
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteFactory->create()->load($quoteId);
            $storeId = $quote->getStoreId();
            if ($cres) {
                $paMD = $this->getRequest()->getParam('threeDSSessionData');
                $resultSubmit3d = $this->_helper->submit3Dv2($cres, $paMD);
            } else {
                $paMD = $this->getRequest()->getParam('MD');
                $resultSubmit3d = $this->_helper->submit3D($pares, $paMD);
            }
            if (isset($resultSubmit3d->status)) {
                //3ds check ok
                $transUrl = $url . '/transactions/' . $paMD;
                $response = $this->_helper->sendRequest($transUrl, null, $storeId);
                $this->_eventManager->dispatch(
                    "magenest_sagepay_save_transaction",
                    ['transaction_data' => $this->sageHelper->getPiResponseData($response, $quote, "Payment")]
                );
                $this->_debug($response);
                if (isset($response['statusCode']) && ($response['statusCode'] == 0000)) {
                    $paymentMethod = $this->sageHelper->arrayObjectToStdClass($response['paymentMethod']);
                    $ccLast4 = $paymentMethod['card']['lastFourDigits'];
                    $expiryDate = $paymentMethod['card']['expiryDate'];
                    $expM = substr($expiryDate, 0, 2);
                    $expY = substr($expiryDate, -2);
                    $ccType = $paymentMethod['card']['cardType'];
                    $payment->setCcLast4($ccLast4);
                    $payment->setCcType($ccType);
                    $payment->setCcExpMonth($expM);
                    $payment->setCcExpYear($expY);

                    $payment->setAdditionalInformation("sagepay_response", $this->_serialize->serialize($response));
                    $payAction = $payment->getAdditionalInformation("sage_payment_action");
                    $totalDue = $order->getTotalDue();
                    $baseTotalDue = $order->getBaseTotalDue();
                    if ($payAction == 'authorize') {
                        $payment->authorize(true, $baseTotalDue);
                        // base amount will be set inside
                        $payment->setAmountAuthorized($totalDue);
                    }
                    if ($payAction == 'authorize_capture') {
                        $payment->setAmountAuthorized($totalDue);
                        $payment->setBaseAmountAuthorized($baseTotalDue);
                        $payment->capture(null);
                    }
                    $payment->setAdditionalInformation("is_sagepay_processing_success", true);
                    // disable because send email confirm twice
                    if ($order->getCanSendNewEmailFlag()) {
                        try {
                            $this->orderSender->send($order);
                        } catch (\Exception $e) {
                            $this->sageLogger->critical($e->getMessage());
                        }
                    }
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->save();
                    return $this->redirectSuccessPage($order);
                } else {
                    $errorMsg = $response['statusDetail'] ?? "3d secure authenticate fail";
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($errorMsg)
                    );
                }
            } else {
                //3ds fail
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('3D Secure Authentication Failed.')
                );
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->sageLogger->critical($e->getMessage());
            usleep(3000000);
            $this->orderObject = null;
            $order = $this->getOrder();
            $payment = $order->getPayment();
            //duplicate response.
            if ($payment && $payment->getAdditionalInformation('is_sagepay_processing_success')) {
                return $this->redirectSuccessPage($order);
            }
            $resultRedirect = $this->resultRedirectFactory->create()->setPath('checkout/cart');
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->sendPaymentFailedEmail->sendPaymentFailedEmail($quote, $e->getMessage());
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->sageLogger->critical($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_url->getUrl('checkout/cart/'));

        return $resultRedirect;
    }

    private function redirectSuccessPage($order)
    {
        $quoteId = $order->getQuoteId();
        $this->checkoutSession->setLastQuoteId($quoteId);
        $this->checkoutSession->setLastSuccessQuoteId($quoteId);
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
        $increment_id = $order->getRealOrderId();
        $this->messageManager->addSuccessMessage("Your order (ID: $increment_id) was successful!");
        return $this->_redirect("checkout/onepage/success");
    }

    private function cancelOrder()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $order->cancel();
        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $order->save();
        $payment = $order->getPayment();
        $payment
            ->setShouldCloseParentTransaction(1)
            ->setIsTransactionClosed(1);
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder()
    {
        if (!$this->orderObject) {
            if ($this->getRequest()->getParam('cres')) {
                $transactionId = $this->getRequest()->getParam('threeDSSessionData');
            } else {
                $transactionId = $this->getRequest()->getParam('MD');
            }
            $transactionModel = $this->_transFactory->create()->load($transactionId, "transaction_id");
            $orderId = $transactionModel->getData('order_id');
            if ($orderId) {
                $order = $this->orderFactory->create()->load($orderId);
                if ($order->getId()) {
                    $this->orderObject = $order;
                }
            }
        }
        if ($this->orderObject) {
            return $this->orderObject;
        } else {
            return $this->checkoutSession->getLastRealOrder();
        }
    }

    /**
     * @param array|string $debugData
     */
    private function _debug($debugData)
    {
        $this->sageLogger->debug(var_export($debugData, true));
    }
}

<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Checkout;

use Magenest\SagepayLib\Classes\SagepayApiException;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;

class ThreedSecure extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var
     */
    protected $_chargeFactory;
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface
     */
    protected $orderStatusHistoryRepository;

    /**
     * ThreedSecure constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     */
    public function __construct(
        Context $context,
        CheckoutSession $session,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $session;
        $this->invoiceSender = $invoiceSender;
        $this->jsonFactory = $resultJsonFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->_formKeyValidator = $formKeyValidator;
        $this->orderRepository = $orderRepository;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * @return false|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magenest\SagepayLib\Classes\SagepayApiException
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            throw new SagepayApiException("Invalid Form Key");
        }
        if ($this->getRequest()->isAjax()) {
            try {
                $order = $this->_checkoutSession->getLastRealOrder();
                /** @var \Magento\Sales\Model\Order\Payment $payment */
                $payment = $order->getPayment();
                $threeDAction = $payment->getAdditionalInformation("sage_3ds_active");
                if ($threeDAction == 'true') {
                    if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                        $order->setStatus('pending_payment');
                        $comment = $order->addStatusHistoryComment(__('Update Order Status to [pending_payment]'));
                        $this->orderStatusHistoryRepository->save($comment);
                        $this->orderRepository->save($order);
                    }
                    $threeDSecureUrl = $payment->getAdditionalInformation("sage_3ds_url");
                    $transId = $payment->getAdditionalInformation("sage_trans_id_secure");
                    if ($payment->getAdditionalInformation("sage_3ds_creq")) {
                        $creq = $payment->getAdditionalInformation("sage_3ds_creq");
                        $formInfo = [
                            'creq' => $creq,
                            'threeDSSessionData' => $transId
                        ];
                    } else {
                        $paReq = $payment->getAdditionalInformation("sage_3ds_pareq");
                        $formInfo = [
                            'PaReq' => $paReq,
                            'TermUrl' => $this->_url->getUrl('sagepay/checkout/redirectBack'),
                            'MD' => $transId
                        ];
                    }


                    return $result->setData([
                        'success' => true,
                        'threeDSercueActive' => true,
                        'threeDSercueUrl' => $threeDSecureUrl,
                        'formData' => $formInfo,
                        'defaultPay' => false
                    ]);
                } else {
                    return $result->setData([
                        'success' => true,
                        'threeDSercueActive' => false,
                        'defaultPay' => true
                    ]);
                }
            } catch (\Exception $e) {
                return $result->setData([
                    'error' => true,
                    'message' => __("Payment exception")
                ]);
            }
        }

        return false;
    }
}

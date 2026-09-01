<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Direct;

use Magenest\SagePay\Setup\UpgradeData;
use Magenest\SagepayLib\Classes\Constants;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magenest\SagepayLib\Classes\SagepayCommon;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;

class PostBack extends Action
{
    const MD_CODE = 'md_code';

    private $order = null;

    protected $sagepayConfig;

    protected $sageHelper;

    protected $checkoutSession;

    protected $sageDirectModel;

    protected $orderRepository;

    protected $orderSender;

    protected $_orderCollection;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * PostBack constructor.
     * @param Context $context
     * @param OrderCollection $orderCollection
     * @param OrderSender $orderSender
     * @param \Magenest\SagePay\Model\SagePayDirect $sagePayDirect
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        OrderCollection $orderCollection,
        OrderSender $orderSender,
        \Magenest\SagePay\Model\SagePayDirect $sagePayDirect,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $session;
        $this->sageHelper = $sageHelper;
        $this->sagepayConfig = $sagePayDirect->getSagePayConfig();
        $this->sageDirectModel = $sagePayDirect;
        $this->orderSender = $orderSender;
        $this->_orderCollection = $orderCollection;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $order = $this->getOrder();
            $payment = $order->getPayment();
            $additionalInformation = $payment->getData('additional_information');
            $threeDSecureResponse = json_decode($additionalInformation['3d_secure_response'], true);
            if (isset($threeDSecureResponse['VPSTxId'])) {
                $vpsTxId = $threeDSecureResponse['VPSTxId'];
                $vpsTxId = preg_replace("/{/", "", $vpsTxId);
                $vpsTxId = preg_replace("/}/", "", $vpsTxId);
                $dataCallback = [
                    'VpsTxId' => $vpsTxId,
                    'CRes' => $this->getRequest()->getParam('cres')
                ];
            } else {
                $dataCallback = filter_input_array(INPUT_POST);
            }
            $postBackResponse = $this->get3DSecureResponse($dataCallback);
            $order = $this->getOrder($postBackResponse);
            if ($this->is3DSecureSuccess($postBackResponse)) {
                $this->finishOrder($order, array_merge($threeDSecureResponse, $postBackResponse));
                $this->_redirect('checkout/onepage/success');
            } else {
                $statusDetail = $postBackResponse['StatusDetail'] ?? __("3d secure authenticate fail");
                $this->messageManager->addError($statusDetail);
                $this->_redirect('checkout/cart');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("The payment is not complete: " . $e->getMessage()));
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * @param $dataCallback
     * @return array
     */
    public function get3DSecureResponse($dataCallback)
    {
        $response = SagepayCommon::requestPost($this->sagepayConfig->getPurchaseUrl('direct3d'), $dataCallback);
        $this->sageHelper->debug('3D secure postback response');
        $this->sageHelper->debug($response);
        return $response;
    }

    /**
     * @param $response
     * @return bool
     */
    public function is3DSecureSuccess($response)
    {
        return in_array(
            $response['Status'],
            [
                Constants::SAGEPAY_REMOTE_STATUS_AUTHENTICATED,
                Constants::SAGEPAY_REMOTE_STATUS_REGISTERED,
                Constants::SAGEPAY_REMOTE_STATUS_OK
            ]
        );
    }

    /**
     * @param $order Order
     * @param $response
     */
    public function finishOrder($order, $response)
    {
        $payment = $order->getPayment();
        if ($payment->getAdditionalInformation('is_authorize')) {
            $this->sageDirectModel->authorizeAndSaveInfo($response, $payment);
        } else {
            $this->sageDirectModel->captureAndSaveInfo($response, $payment);
        }
        $this->orderSender->send($order);
        $this->orderRepository->save($order);
    }

    public function getOrder($response = null)
    {
        if ($this->order == null) {
            $order = $this->checkoutSession->getLastRealOrder();
            $mdCode = $this->getRequest()->getParam('MD') ??
                $this->getRequest()->getParam('threeDSSessionData'); // 3D normal will return MD, 3Ds2 will return cres
            if ($order === null || $order->getId() === null || $mdCode === null || $order->getMdCode() != $mdCode) {
                $order = $this->_orderCollection->create()
                    ->addFieldToFilter(self::MD_CODE, $mdCode)
                    ->setPageSize(1)
                    ->getFirstItem();
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
                        $this->_customerSession->setIsLoggedIn('true');
                        $this->_customerSession->setCustomerId($customerId);
                    }
                }
            }
            if (!$order->getId()) {
                throw new LocalizedException(__("Something went wrong. Please try again later."));
            }
            $this->order = $order;
        }

        return $this->order;
    }

    /**
     * @throws LocalizedException
     */
    public function cancelOrder()
    {
        $order = $this->getOrder();
        $order->cancel();
        $this->orderRepository->save($order);
        $this->checkoutSession->restoreQuote();
    }
}

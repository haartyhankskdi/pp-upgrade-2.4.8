<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magenest\SagePay\Model\ResourceModel\Profile as Resource;
use Magenest\SagePay\Model\ResourceModel\Profile\Collection as Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magenest\SagePay\Helper\Subscription;

class Profile extends AbstractModel
{

    /**
     * @var string
     */
    protected $_eventPrefix = 'profile_';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var \Magenest\SagePay\Helper\SageHelper
     */
    protected $sagehelper;

    /**
     * @var \Magenest\SagePay\Helper\Logger
     */
    protected $sageLogger;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @var ProfileFactory
     */
    protected $_profileFactory;

    /**
     * @var \Magenest\SagePay\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Profile constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magenest\SagePay\Model\ResourceModel\Profile $resource
     * @param \Magenest\SagePay\Model\ResourceModel\Profile\Collection $resourceCollection
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magenest\SagePay\Model\TransactionFactory $transactionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magenest\SagePay\Helper\SageHelper $sageHelper
     * @param \Magenest\SagePay\Helper\Data $dataHelper
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Magenest\SagePay\Model\ProfileFactory $profileFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        Context $context,
        Registry $registry,
        Resource $resource,
        Collection $resourceCollection,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magenest\SagePay\Model\TransactionFactory $transactionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magenest\SagePay\Helper\SageHelper $sageHelper,
        \Magenest\SagePay\Helper\Data $dataHelper,
        \Magenest\SagePay\Helper\Logger $sageLogger,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magenest\SagePay\Model\ProfileFactory $profileFactory,
        $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->sagehelper = $sageHelper;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->transactionFactory = $transactionFactory;
        $this->_storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->orderFactory = $orderFactory;
        $this->customerRepository = $customerRepository;
        $this->sageLogger = $sageLogger;
        $this->orderSender = $orderSender;
        $this->stockState = $stockState;
        $this->_profileFactory = $profileFactory;
        $this->_dataHelper = $dataHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function reOrder()
    {
        $store = $this->_storeManager->getStore(1); // set default store for repeat transaction
        $websiteId = $store->getWebsiteId();

        $orderId = $this->getData('order_id');
        /** @var \Magento\Sales\Model\Order $orderModel */
        $orderModel = $this->orderFactory->create();
        $origOrder = $orderModel->loadByIncrementId($orderId);
        $firstTransactionId = $this->getData('transaction_id');
        /** @var \Magenest\SagePay\Model\Transaction $transModel */
        $transModel = $this->transactionFactory->create();
        $customerId = $origOrder->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $customer->setWebsiteId($websiteId);
        $productId = $origOrder->getAllItems()[0]->getProductId();
        $productSku = $origOrder->getAllItems()[0]->getSku();
        $productQty = $origOrder->getAllItems()[0]->getQtyOrdered();
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create()->loadByAttribute('sku', $productSku);
        $product->setWebsiteIds(['0', '1']);
        $stock = $this->stockState->getStockQty($product->getEntityId());
        $salableQty = $this->getSalableQuantityDataBySku->execute($productSku)[0];
        if ($product->getIsSalable() && ($stock || !$salableQty['manage_stock'])) {
            // Create Order
            $cart_id = $this->cartManagementInterface->createEmptyCart();
            $quote = $this->cartRepositoryInterface->get($cart_id);
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote->setStore($store);
            $quote->setCurrency();
            $quote->save();
            $quote->assignCustomer($customer);
            $quote->addProduct($product, (int)$productQty);
            $quote->setCustomerIsGuest(false);

            $shippingInput = $this->getOriginShippingInfo($origOrder);
            $paymentInput = $this->getOriginPaymentInfo($origOrder);
            if ($shippingInput) {
                $quote->getShippingAddress()->addData($shippingInput);
            }
            if ($paymentInput) {
                $quote->getBillingAddress()->addData($paymentInput);
            }
            $shippingAddress = $quote->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($origOrder->getShippingMethod()); //shipping method

            $quote->setPaymentMethod(SagePay::CODE); //payment method
            $quote->setInventoryProcessed(false);
            $quote->setCouponCode($origOrder->getDiscountDescription());
            $quote->setAppliedRuleIds($origOrder->getAppliedRuleIds());
            $quote->getPayment()->importData(['method' => SagePay::CODE, 'is_sage_subscription_payment' => true]);
            $quote->collectTotals()->save();
            $quote = $this->cartRepositoryInterface->get($quote->getId());
            /** @var \Magento\Sales\Model\Order $newOrderModel */
            $newOrderModel = $this->cartManagementInterface->submit($quote);

            $res = $this->sendRepeatRequest($firstTransactionId, $newOrderModel);
            $this->sageLogger->debug(var_export($res, true));
            if (isset($res['statusCode']) && $res['statusCode'] == "0000") {
                $transactionId = $res['transactionId'];
                $ccLast4 = $res['paymentMethod']['card']['lastFourDigits'];
                $expMonth = substr($res['paymentMethod']['card']['expiryDate'], 2);
                $expYear = "20" . substr($res['paymentMethod']['card']['expiryDate'], -2);
                $ccType = $res['paymentMethod']['card']['cardType'];
                $payment = $newOrderModel->getPayment();
                $payment->setCcLast4($ccLast4);
                $payment->setCcExpMonth($expMonth);
                $payment->setCcExpYear($expYear);
                $payment->setCcType($ccType);
                $payment->setTransactionId($transactionId);
                $payment->setAdditionalInformation("sage_trans_id", $transactionId);
                $payment->setIsTransactionClosed(0);
                $newOrderModel->setPayment($payment);
                $newOrderModel->addStatusHistoryComment("Payment status detail: " . $res['statusDetail']);
                $newOrderModel->addStatusHistoryComment("Payment status: " . $res['status']);
                $payment->registerCaptureNotification($this->_dataHelper->getPayAmount($newOrderModel));
                $newOrderModel->save();
                $newOrderId = $newOrderModel->getIncrementId();
                $this->updateProfileRecord($newOrderId);

                if ($newOrderModel->getCanSendNewEmailFlag()) {
                    $this->orderSender->send($newOrderModel);
                }
                $data = [
                    'transaction_id' => $transactionId,
                    'transaction_type' => $res['transactionType'],
                    'transaction_status' => $res['status'],
                    'card_secure' => '',
                    'status_detail' => $res['statusDetail'],
                    'order_id' => $newOrderId,
                    'customer_id' => $newOrderModel->getCustomerId(),
                    'is_subscription' => "1"
                ];
                $transModel->setData($data)->save();
            } else {
                $this->endSubscription();
                $newOrderModel->cancel();
                $this->orderRepository->save($newOrderModel);
                throw new \Magento\Framework\Exception\LocalizedException($res['statusDetail']);
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function endSubscription() {
        $profileId = $this->getData('id');
        $profileModel = $this->_profileFactory->create()->load($profileId);
        $profileModel->setData('status', Subscription::SUBS_STAT_END_CODE);
        $profileModel->save();
    }

    /**
     * @param $transId
     * @param \Magento\Sales\Model\Order $order
     * @return array|string
     */
    public function sendRepeatRequest($transId, $order)
    {
        $url = $this->sagehelper->getPiEndpointUrl() . '/transactions';
        $payload = $this->sagehelper->buildRepeatQuery($transId, $order);

        $res = $this->sagehelper->sendRequest($url, $payload);

        return $res;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array|bool
     */
    public function getOriginShippingInfo($order)
    {
        $address = $order->getShippingAddress();
        if ($address) {
            /** @var \Magento\Sales\Model\Order\Address $address */
            $address = $order->getShippingAddress();
            return $this->handleAddressInfo($order, $address);
        }

        return false;
    }

    /**
     * @param $order
     * @param $address
     * @return array
     */
    private function handleAddressInfo($order, $address)
    {
        $streetArr = $address->getStreet();
        $streetFull = $streetArr[0] ?? '';

        return [
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'city' => $address->getCity(),
            'postcode' => $address->getPostcode(),
            'telephone' => $address->getTelephone(),
            'street' => $streetFull,
            'customer_id' => $order->getCustomerId(),
            'email' => $order->getCustomerEmail(),
            'region' => $address->getRegion(),
            'regionCode' => $address->getRegionCode(),
            'region_id' => $address->getRegionId(),
            'country_id' => $address->getCountryId()
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array|bool
     */
    public function getOriginPaymentInfo($order)
    {
        /** @var \Magento\Sales\Model\Order\Address $address */
        $address = $order->getBillingAddress();
        if ($address) {
            return $this->handleAddressInfo($order, $address);
        }

        return false;
    }

    /**
     * @param $newOrderId
     * @throws \Exception
     */
    public function updateProfileRecord($newOrderId)
    {
        $remaining = $this->getData('remaining_cycles');
        --$remaining;
        $lastBilled = date('Y-m-d');
        $nextBilling = date('Y-m-d', strtotime("+ " . $this->getData('frequency')));
        $this->addData([
            'remaining_cycles' => $remaining,
            'last_billed' => $lastBilled,
            'next_billing' => $nextBilling
        ]);
        if ($remaining == 0) {
            $this->addData(['status' => Subscription::SUBS_STAT_END_CODE]);
        }
        $this->save();
        $this->addSequenceOrder($newOrderId);
    }

    /**
     * @throws \Exception
     */
    public function cancelSubscription()
    {
        $this->addData(['status' => Subscription::SUBS_STAT_CANCELLED_CODE]);
        $this->save();
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $status = $this->getData('status');
        switch ($status) {
            case Subscription::SUBS_STAT_ACTIVE_CODE:
                return Subscription::SUBS_STAT_ACTIVE_TEXT;
            case Subscription::SUBS_STAT_INACTIVE_CODE:
                return Subscription::SUBS_STAT_INACTIVE_TEXT;
            case Subscription::SUBS_STAT_END_CODE:
                return Subscription::SUBS_STAT_END_TEXT;
            case Subscription::SUBS_STAT_CANCELLED_CODE:
                return Subscription::SUBS_STAT_CANCELLED_TEXT;
            default:
                return "Unknown";
        }
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function isOwn(
        $customerId
    ) {
        return $this->getData('customer_id') == $customerId;
    }

    /**
     * @return bool
     */
    public function canCancel()
    {
        return $this->getData('status') != Subscription::SUBS_STAT_CANCELLED_CODE;
    }

    /**
     * @param $orderId
     * @throws \Exception
     */
    public function addSequenceOrder(
        $orderId
    ) {
        $sequenceOrderIds = $this->getData('sequence_order_ids');
        $newOrderId = ($sequenceOrderIds == null) ? $orderId : $sequenceOrderIds . "-" . $orderId;
        $this->addData(['sequence_order_ids' => $newOrderId])->save();
    }
}

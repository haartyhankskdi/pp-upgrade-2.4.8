<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use AutifyDigital\LloydscardnetPayment\Logger\Logger as AutifyDigitalLcLogger;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory as LcPaymentsFactory;
use AutifyDigital\LloydscardnetPayment\Model\PaymentTokenFactory as LcPaymentTokenFactory;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory; // phpcs:ignore
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Api\PaymentsRepositoryInterface;
use AutifyDigital\LloydscardnetPayment\Model\ResourceModel\Payments\CollectionFactory as PaymentsCollectionFactory;
use AutifyDigital\LloydscardnetPayment\Api\PaymentTokenRepositoryInterface as LcPaymentTokenRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\WebsiteRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Sql\Expression;

/**
 * Helper Data Class
 */
class Data extends AbstractHelper
{
    /* Sensitive field patterns to mask in logs */
    private const MASK_FIELDS = [
        'cardNumber', 'card_number', 'securityCode',
        'token', 'paymentToken', 'hosteddataid', 'value',
        'name', 'bname', 'sname', 'email', 'phone', 'telephone',
        'address', 'address1', 'address2', 'baddr1', 'baddr2', 'saddr1', 'saddr2', 'street',
        'authorizationCode', 'approvalCode', 'schemeTransactionId',
        'paymentMethodType', 'paymentMethodBrand', 'ipgTransactionId', 'firstName', 'lastName',
        'paymentToken', 'Api-Key', 'api_key', 'apiSecret', 'api_secret',
        // Apple Pay payment token envelope (cardholder-data envelope / crypto material)
        'data', 'signature', 'ephemeralPublicKey', 'publicKeyHash',
        // Apple Pay contact fields
        'givenName', 'familyName', 'emailAddress', 'phoneNumber', 'addressLines',
        'locality', 'administrativeArea',
        // Address / PII
        'postalCode', 'postcode', 'zip', 'bzip', 'szip',
        'city', 'bcity', 'scity', 'region', 'bstate', 'sstate',
        'company', 'bcompany', 'scompany'
    ];

    /** Finace Options API */
    public const PAYMENTS_API_URL = 'payments';
    public const FISERV_API_URL = 'checkouts';
    public const PATCH_PAYMENTS_API_URL = 'payments/{ipgTransactionId}';
    public const API_URL = 'https://plugin-licenses.autify.co.uk/wp-json/autifydigital/v1/plugin';
    public const MODULE_NAME = 'LBOP Magento eCom';
    public const MODULE_VERSION = '3.0.15';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var WebsiteRepository
     */
    protected $websiteRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var string
     */
    public const LLOYDS_COOKIE = 'autify_lloyds_cookie';

    /**
     * @var string
     */
    public const LLOYDS_PAYMENT_JS_KEY = 'paymentjs_token';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var AutifyDigitalLcLogger
     */
    protected $autifyLcLogger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;

     /**
      * Curl
      * @var object
      */
    protected $ch;

    /**
     * Request timeout
     * @var int type
     */
    protected $timeout = 40;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var LcPaymentsFactory
     */
    private $lcPaymentsFactory;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $priceHelper;

    /**
     * @var CookieManagerInterface $cookieManager
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory $cookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface $sessionManager
     */
    private $sessionManager;

    /**
     * @var \Magento\Framework\Locale\Resolver $store
     */
    protected $store;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService $invoiceService
     */
    protected $invoiceService;

    /**
     * @var LcPaymentTokenFactory $lcPaymentTokenFactory
     */
    protected $lcPaymentTokenFactory;

    /**
     * @var PaymentTokenCollectionFactory $paymentTokenCollectionFactory
     */
    protected $paymentTokenCollectionFactory;

    /**
     * @var DateTime $dateTime
     */
    protected $dateTime;

    /**
     * @var Header $httpHeader
     */
    protected $httpHeader;

    /**
     * @var RemoteAddress $remoteAddress
     */
    protected $remoteAddress;

    /**
     * @var CustomerRepositoryInterface|null
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder|null
     */
    private $searchCriteriaBuilder;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var PaymentsRepositoryInterface
     */
    protected $paymentsRepository;

    /**
     * @var PaymentsCollectionFactory
     */
    protected $paymentsCollectionFactory;

    /**
     * @var LcPaymentTokenRepositoryInterface
     */
    protected $lcPaymentTokenRepository;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $adminSession;

    /**
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \AutifyDigital\LloydscardnetPayment\Logger\Logger $autifyLcLogger
     * @param \AutifyDigital\LloydscardnetPayment\Model\Config $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\Locale\Resolver $store
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentsFactory $lcPaymentsFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \AutifyDigital\LloydscardnetPayment\Model\PaymentTokenFactory $lcPaymentTokenFactory
     * @param \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\CollectionFactory $paymentTokenCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Sales\Model\Order $_order
     * @param ModuleListInterface $moduleList
     * @param WriterInterface $configWriter
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentsRepositoryInterface $paymentsRepository
     * @param PaymentsCollectionFactory $paymentsCollectionFactory
     * @param LcPaymentTokenRepositoryInterface $lcPaymentTokenRepository
     * @param StoreManagerInterface $storeManager,
     * @param WebsiteRepository $websiteRepository,
     * @param ScopeConfigInterface $scopeConfig,
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        AutifyDigitalLcLogger $autifyLcLogger,
        Config $config,
        TypeListInterface $cacheTypeList,
        CheckoutSession $checkoutSession,
        TimezoneInterface $timezoneInterface,
        \Magento\Framework\Locale\Resolver $store,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        LcPaymentsFactory $lcPaymentsFactory,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        LcPaymentTokenFactory $lcPaymentTokenFactory,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        Header $httpHeader,
        RemoteAddress $remoteAddress,
        EncryptorInterface $encryptor,
        UrlInterface $urlBuilder,
        SerializerInterface $serializer,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        Curl $curl,
        Repository $assetRepo,
        Order $_order,
        ModuleListInterface $moduleList,
        WriterInterface $configWriter,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        OrderRepositoryInterface $orderRepository,
        PaymentsRepositoryInterface $paymentsRepository,
        PaymentsCollectionFactory $paymentsCollectionFactory,
        LcPaymentTokenRepositoryInterface $lcPaymentTokenRepository,
        StoreManagerInterface $storeManager,
        WebsiteRepository $websiteRepository,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Backend\Model\Auth\Session $adminSession
    ) {
        parent::__construct($context);
        $this->autifyLcLogger = $autifyLcLogger;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->checkoutSession = $checkoutSession;
        $this->timezoneInterface = $timezoneInterface;
        $this->store = $store;
        $this->orderSender = $orderSender;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceSender = $invoiceSender;
        $this->lcPaymentsFactory = $lcPaymentsFactory;
        $this->priceHelper = $priceHelper;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->invoiceService = $invoiceService;
        $this->lcPaymentTokenFactory = $lcPaymentTokenFactory;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->dateTime = $dateTime;
        $this->httpHeader = $httpHeader;
        $this->remoteAddress = $remoteAddress;
        $this->encryptor = $encryptor;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
        $this->jsonHelper = $jsonHelper;
        $this->curl = $curl;
        $this->assetRepo = $assetRepo;
        $this->_order = $_order;
        $this->moduleList = $moduleList;
        $this->configWriter = $configWriter;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentsCollectionFactory = $paymentsCollectionFactory;
        $this->lcPaymentTokenRepository = $lcPaymentTokenRepository;
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->scopeConfig = $scopeConfig;
        $this->adminSession = $adminSession;
    }

    /**
     * Get the name of the admin user performing the current action.
     *
     * @return string
     */
    public function getRefundIssuerName()
    {
        $adminUser = $this->adminSession->getUser();
        if ($adminUser && $adminUser->getId()) {
            return trim($adminUser->getFirstName() . ' ' . $adminUser->getLastName())
                . ' (' . $adminUser->getUserName() . ')';
        }

        return 'System';
    }

    /**
     * Get payment Mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->config->getConfig('payment/basic/lloyds_mode');
    }

    /**
     * Add log with automatic sanitization
     *
     * @param mixed $message
     * @param bool $array
     * @return void
     */
    public function addLog(mixed $message,bool $array = false): void
    {
        if ($this->config->getConfig('payment/basic/log') !== '1') {
            return;
        }

        $data = $message;
        
        if (is_string($message)) {
            try {
                $decoded = $this->serializer->unserialize($message);
                $data = $decoded;
                $array = true;
            } catch (\InvalidArgumentException $e) {
                $data = $message;
            }
        }

        $sanitized = $this->sanitize($data);

        if ($array === true) {
            $this->autifyLcLogger->info(
                "message:\n" . $this->serializer->serialize($sanitized)
            );
        } else {
            $this->autifyLcLogger->info((string)$sanitized);
        }
    }

    /**
     * Sanitize sensitive data recursively
     *
     * @param mixed $data
     * @return mixed
     */
    private function sanitize(mixed $data): mixed
    {
        if (is_object($data)) {
            try {
                $data = $this->serializer->unserialize(
                    $this->serializer->serialize($data)
                );
            } catch (\InvalidArgumentException $e) {
                return $data;
            }
        }

        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if ($this->shouldMask((string)$key)) {
                $data[$key] = match(true) {
                    is_scalar($value) || $value === null => $this->maskValue($value),
                    is_array($value) => $this->sanitize($value),
                    is_object($value) => $this->sanitize($value),
                    default => $value
                };
            } else {
                $data[$key] = match(true) {
                    is_array($value) => $this->sanitize($value),
                    is_object($value) => $this->sanitize($value),
                    default => $value
                };
            }
        }

        return $data;
    }

    /**
     * Mask value showing only last 4 characters
     *
     * @param mixed $value
     */
    private function maskValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return '***';
        }

        $str = (string)$value;
        $len = strlen($str);
        
        if ($len === 0) {
            return '';
        }
        
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        
        return str_repeat('*', $len - 4) . substr($str, -4);
    }

    /**
     * Check if field should be masked
     *
     * @param string $key
     * @return bool
     */
    private function shouldMask($key)
    {
        $normalized = strtolower(str_replace(['-', '_'], '', (string)$key));
        
        foreach (self::MASK_FIELDS as $field) {
            if ($normalized === strtolower(str_replace(['-', '_'], '', $field))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Restore quote
     */
    public function restoreQuote()
    {
        $this->checkoutSession->restoreQuote();
    }

    /**
     * Get Order
     */
    public function getOrderSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Get Store
     *
     * @return \Magento\Framework\Locale\Resolver
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->store;
    }

   /**
    * Get Time Zone
    *
    * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
    */
    public function timezone()
    {
        return $this->timezoneInterface;
    }

    /**
     * Get Http Header
     *
     * @return \Magento\Framework\HTTP\Header
     * @throws \Exception
     */
    public function getHttpHeader()
    {
        return $this->httpHeader;
    }

    /**
     * Get Remote Address
     *
     * @return string
     * @throws \Exception
     */
    public function getRemoteIp()
    {
        $ip = $this->remoteAddress->getRemoteAddress();
        if (preg_match('/::ffff:(\d+\.\d+\.\d+\.\d+)/', $ip, $m)) {
            return $m[1];
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : '';
    }

    /**
     * Create Hash
     *
     * @param string $storeName
     * @param string|int $transactionTime
     * @param mixed $chargeTotal
     * @param mixed $currency
     * @param int|string|null $storeId
     * @return string
     */
    public function createHash($storeName, $transactionTime, $chargeTotal, $currency, $storeId = null)
    {
        $sharedSecret = $this->config->getSharedSecret($storeId);
        $stringToHash = $storeName . $transactionTime . $chargeTotal . $currency . $sharedSecret;
        $ascii = bin2hex($stringToHash);
        return hash('sha256', $ascii);
    }

    /**
     * Verify Response
     *
     * @param string $response_hash
     * @param string|int $transactionTime
     * @param string|int $approvalCode
     * @param string|int $chargeTotal
     * @param string $currency
     * @param string|int $storeName
     * @param int|string|null $storeId
     * @return bool
     */
    public function verifyResponse($response_hash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeName, $storeId = null)
    {
        $sharedSecret = $this->config->getSharedSecret($storeId);
        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currency . $transactionTime . $storeName;
        $ascii = bin2hex($stringToHash);
        $myHash = hash('sha256', $ascii);

        if ($myHash === $response_hash) {
            return true;
        }
        return false;
    }

   /**
    * Verify Response Notification
    *
    * @param string $notification_hash
    * @param string|int $transactionTime
    * @param string|int $approvalCode
    * @param string|int $chargeTotal
    * @param string $currency
    * @param string|int $storeName
    * @param int|string|null $storeId
    * @return bool
    */
    public function verifyResponseNotification(
        $notification_hash,
        $transactionTime,
        $approvalCode,
        $chargeTotal,
        $currency,
        $storeName,
        $storeId = null
    ) {
        $sharedSecret = $this->config->getSharedSecret($storeId);

        $notificationStringToHash = $chargeTotal . $sharedSecret . $currency . $transactionTime . $storeName . $approvalCode; // phpcs:ignore

        $asciinotificationNewHash = bin2hex($notificationStringToHash);

        $notificationNewHash = hash('sha256', $asciinotificationNewHash);

        if ($notificationNewHash === $notification_hash) {
            return true;
        }
        return false;
    }

    /**
     * Start With
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     * */
    public function startsWith($haystack, $needle)
    {
        if (substr($haystack, 0, strlen($needle)) === $needle) {
            return true;
        }
        return false;
    }

    /**
     * Get Payment By Order Id
     *
     * @return mixed
     */
    public function getLCPaymentFactory()
    {
        return $this->lcPaymentsFactory->create();
        ;
    }

    /**
     * Get Payment By Order Id
     *
     * @param string $orderId
     * @return mixed
     */
    public function getPaymentByOrderId($orderId)
    {
        $payment = $this->paymentsCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->getFirstItem(); // phpcs:ignore

        return $payment;
    }

   /**
    * Send Order Email
    *
    * @param string $orderId
    * @return object
    */
    public function getPaymentByLcOrderId($orderId)
    {
        $payment = $this->paymentsCollectionFactory->create()
            ->addFieldToFilter('cardnet_order_id', $orderId)
            ->getFirstItem(); // phpcs:ignore

        return $payment;
    }

    /**
     * Send Order Email
     *
     * @param object $order
     * @return bool
     */
    public function sendOrderEmail($order)
    {
        return $this->orderSender->send($order);
    }

   /**
    * End With
    *
    * @param string|int $haystack
    * @param string|int $needle
    * @return bool
    * */
    public function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return false;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Convert In Price Format
     *
     * @param float|string $price
     * @return float|string
     */
    public function priceFormat($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    /**
     * Cancel Order by Order Id
     *
     * @param Order $order
     */
    public function cancelOrder($order)
    {
        if ($order->getStatus() == "pending" || $order->getStatus() == "pending_payment") {
            $order->cancel();
            $orderState = Order::STATE_CANCELED;
            $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
            $order->setCanSendNewEmailFlag(false);
            $this->orderRepository->save($order);
        } else {
            $orderState = Order::STATE_CANCELED;
            $order->setState($orderState)->setStatus(Order::STATE_CANCELED);
            $order->setCanSendNewEmailFlag(false);
            $this->orderRepository->save($order);
        }
    }

    /**
     * Process Order
     *
     * @param Order $order
     * @param string|int $emailAlreadySent Flag set to '1' when the order-confirmation email
     *                                     was already dispatched during redirect; suppresses
     *                                     the invoice email to avoid duplicates.
     * @param string|int $paymentjs
     */
    public function processOrder($order, $emailAlreadySent = 0, $paymentjs = 0)
    {
        $paymentModel = $this->getPaymentByOrderId($order->getId());
        if (!$this->claimOrderProcessingLock((int) $paymentModel->getId())) {
            $this->addLog('processOrder: Order ' . $order->getIncrementId() . ' already being processed, skipping');
            return $this->orderRepository->get($order->getId());
        }

        $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action');

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW) {
            $this->addLog('Clearing payment review status for order: ' . $order->getIncrementId());
        }

        if ($paymentAction === 'authorize' && $paymentjs == 1) {
            $orderState = Order::STATE_PENDING_PAYMENT;
            $order->setState($orderState)->setStatus(Order::STATE_PENDING_PAYMENT);

            /** @var \Magento\Sales\Model\Order\Payment|null $payment */
            $payment = $order->getPayment();
            if ($payment) {
                $payment->setIsTransactionPending(false);
            }
        } else {
            $orderState = Order::STATE_PROCESSING;
            $order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
        }

        $this->orderRepository->save($order);

        $orderPayment = $this->getPaymentByOrderId($order->getId());
        if ($orderPayment->getRedirectEmailSent() != '1') {
            $orderPayment->setData('redirect_email_sent', 1);
            $this->paymentsRepository->save($orderPayment);
            $this->sendOrderEmail($order);
        }

        $this->generateInvoice($order, $emailAlreadySent, $paymentjs);

        return $order;
    }

    /**
     * Atomically claim the order processing lock using the payment model's status field.
     *
     * @param int $paymentId
     * @return bool true if lock was acquired, false if already processed
     */
    private function claimOrderProcessingLock(int $paymentId): bool
    {
        $resource = $this->paymentsCollectionFactory->create()->getResource();
        $connection = $resource->getConnection();
        $tableName = $resource->getMainTable();

        $affectedRows = $connection->update(
            $tableName,
            ['order_processed' => 1],
            [
                'payments_id = ?' => $paymentId,
                'order_processed = ?' => 0
            ]
        );

        return $affectedRows > 0;
    }

    /**
     * Generate Invoice
     *
     * @param Order $order
     * @param string|int $emailAlreadySent Flag set to '1' when the order-confirmation email
     *                                     was already dispatched during redirect; suppresses
     *                                     the invoice email to avoid duplicates.
     * @param string|int $paymentjs
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateInvoice($order, $emailAlreadySent = 0, $paymentjs = 0)
    {
        $paymentAction = $this->config->getConfig('payment/lcnetpaymentjs/payment_action');

        if (
            $paymentAction == 'authorize_capture' ||
            ($paymentAction == 'authorize' && $paymentjs == 0)
        ) {
            $resource = $this->paymentsCollectionFactory->create()->getResource();
            $connection = $resource->getConnection();

            $connection->beginTransaction();
            try {
                $connection->fetchRow(
                    $connection->select()
                        ->from($resource->getTable('sales_order'), ['entity_id'])
                        ->where('entity_id = ?', $order->getId())
                        ->forUpdate()
                );

                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($order->getId());

                if (!$order->hasInvoices()) {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setData('requested_capture_case', \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->getOrder()->setCustomerNoteNotify(0);
                    $invoice->setData('is_in_process', true);
                    $invoice->setTransactionId($order->getId());
                    $order->addStatusHistoryComment('Invoice was generated after receiving the payment', false);

                    $dbTransaction = $this->transactionFactory->create()
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());

                    $dbTransaction->save();
                    if ($emailAlreadySent !== '1') {
                        $this->invoiceSender->send($invoice);
                    }
                    $this->orderRepository->save($order);
                } else {
                    $this->addLog('Invoice already exists for order: ' . $order->getIncrementId() . ', skipping');
                }

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                $this->addLog('generateInvoice error: ' . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Detect Card Type
     *
     * @param string $cardNumber
     * @return string
     * */
    public function detectCardType($cardNumber)
    {
        $regularExpressionWithKeys = [
            'electron' => '/^(4026|417500|4405|4508|4844|4913|4917)\d+$/',
            'maestro' => '/^(5018|5020|5038|5612|5893|6304|6705|6759|6761|6762|6766|6763|6777|0604|6390)\d+$/',
            'dankort' => '/^(5019)\d+$/',
            'interpayment' => '/^(636)\d+$/',
            'unionpay' => '/^(62|88)\d+$/',
            'visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'mastercard' => '/^5[1-5][0-9]{14}$/',
            'amex' => '/^3[47][0-9]{13}$/',
            'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/'
        ];

        foreach ($regularExpressionWithKeys as $key => $value) {
            if (preg_match($value, $cardNumber)) {
                return $key;
            }
        }
        return '';
    }

    /**
     * Get ISO Currency Code
     *
     * @param string $currencyCode
     * @return int
     * */
    public function getIsoCurrencyCodeFromCurrencyCode($currencyCode)
    {
        $isoCurrencyCodeList = [
            "AED" => 784,
            "AFN" => 971,
            "ALL" => 8,
            "AMD" => 51,
            "ANG" => 532,
            "AOA" => 973,
            "ARS" => 32,
            "AUD" => 36,
            "AWG" => 533,
            "AZN" => 944,
            "BAM" => 977,
            "BBD" => 52,
            "BDT" => 50,
            "BGN" => 975,
            "BHD" => 48,
            "BIF" => 108,
            "BMD" => 60,
            "BND" => 96,
            "BOB" => 68,
            "BOV" => 984,
            "BRL" => 986,
            "BSD" => 44,
            "BTN" => 64,
            "BWP" => 72,
            "BYR" => 974,
            "BZD" => 84,
            "CAD" => 124,
            "CDF" => 976,
            "CHE" => 947,
            "CHF" => 756,
            "CHW" => 948,
            "CLF" => 990,
            "CLP" => 152,
            "CNY" => 156,
            "COP" => 170,
            "COU" => 970,
            "CRC" => 188,
            "CUC" => 931,
            "CUP" => 192,
            "CVE" => 132,
            "CZK" => 203,
            "DJF" => 262,
            "DKK" => 208,
            "DOP" => 214,
            "DZD" => 12,
            "EGP" => 818,
            "ERN" => 232,
            "ETB" => 230,
            "EUR" => 978,
            "FJD" => 242,
            "FKP" => 238,
            "GBP" => 826,
            "GEL" => 981,
            "GHS" => 936,
            "GIP" => 292,
            "GMD" => 270,
            "GNF" => 324,
            "GTQ" => 320,
            "GYD" => 328,
            "HKD" => 344,
            "HNL" => 340,
            "HRK" => 191,
            "HTG" => 332,
            "HUF" => 348,
            "IDR" => 360,
            "ILS" => 376,
            "INR" => 356,
            "IQD" => 368,
            "IRR" => 364,
            "ISK" => 352,
            "JMD" => 388,
            "JOD" => 400,
            "JPY" => 392,
            "KES" => 404,
            "KGS" => 417,
            "KHR" => 116,
            "KMF" => 174,
            "KPW" => 408,
            "KRW" => 410,
            "KWD" => 414,
            "KYD" => 136,
            "KZT" => 398,
            "LAK" => 418,
            "LBP" => 422,
            "LKR" => 144,
            "LRD" => 430,
            "LSL" => 426,
            "LTL" => 440,
            "LVL" => 428,
            "LYD" => 434,
            "MAD" => 504,
            "MDL" => 498,
            "MGA" => 969,
            "MKD" => 807,
            "MMK" => 104,
            "MNT" => 496,
            "MOP" => 446,
            "MRO" => 478,
            "MUR" => 480,
            "MVR" => 462,
            "MWK" => 454,
            "MXN" => 484,
            "MXV" => 979,
            "MYR" => 458,
            "MZN" => 943,
            "NAD" => 516,
            "NGN" => 566,
            "NIO" => 558,
            "NOK" => 578,
            "NPR" => 524,
            "NZD" => 554,
            "OMR" => 512,
            "PAB" => 590,
            "PEN" => 604,
            "PGK" => 598,
            "PHP" => 608,
            "PKR" => 586,
            "PLN" => 985,
            "PYG" => 600,
            "QAR" => 634,
            "RON" => 946,
            "RSD" => 941,
            "RUB" => 643,
            "RWF" => 646,
            "SAR" => 682,
            "SBD" => 90,
            "SCR" => 690,
            "SDG" => 938,
            "SEK" => 752,
            "SGD" => 702,
            "SHP" => 654,
            "SLL" => 694,
            "SOS" => 706,
            "SRD" => 968,
            "SSP" => 728,
            "STD" => 678,
            "SYP" => 760,
            "SZL" => 748,
            "THB" => 764,
            "TJS" => 972,
            "TMT" => 934,
            "TND" => 788,
            "TOP" => 776,
            "TRY" => 949,
            "TTD" => 780,
            "TWD" => 901,
            "TZS" => 834,
            "UAH" => 980,
            "UGX" => 800,
            "USD" => 840,
            "USN" => 997,
            "USS" => 998,
            "UYI" => 940,
            "UYU" => 858,
            "UZS" => 860,
            "VEF" => 937,
            "VND" => 704,
            "VUV" => 548,
            "WST" => 882,
            "XAF" => 950,
            "XCD" => 951,
            "XOF" => 952,
            "XPF" => 953,
            "YER" => 886,
            "ZAR" => 710,
            "ZMW" => 967,
        ];

        return isset($isoCurrencyCodeList[trim(strtoupper($currencyCode))]) ?
        $isoCurrencyCodeList[trim(strtoupper($currencyCode))] : 0;
    }

    /**
     * Get data from cookie
     *
     * @return string
     */
    public function getLloydsCookie()
    {
        return $this->cookieManager->getCookie(self::LLOYDS_COOKIE);
    }

    /**
     * Set data to cookie
     *
     * @param string $value
     * @param int $duration
     *
     * @return void
     */
    public function setLloydsCookie($value, $duration = 86400)
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->cookieManager->setPublicCookie(
            self::LLOYDS_COOKIE,
            $value,
            $metadata
        );
    }

    /**
     * Delete cookie
     *
     * @return void
     */
    public function deleteLloydsCookie()
    {
        $this->cookieManager->deleteCookie(
            self::LLOYDS_COOKIE,
            $this->cookieMetadataFactory
                ->createCookieMetadata()
                ->setPath($this->sessionManager->getCookiePath())
                ->setDomain($this->sessionManager->getCookieDomain())
        );
    }

    /**
     * Call Curl function for paymentjs
     *
     * @param string $path
     * @param array $postArray
     * @param string $type
     *
     * @return array
     *
     * // @codingStandardsIgnoreStart
     */
    public function callCurl($path, $postArray = [], $type = 'POST', $apiUrl = '', $storeId = null)
    {
        $responseArray = [];
        $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
        $config = $this->config->getBasicConfigurations($mode, $storeId);
        $secretKey = $config['api_secret'];
        $apiKey = $config['api_key'];
        $timestamp = (int)(microtime(true) * 1000);
        $contentType = 'application/json';
        $jsonPayload = $this->jsonHelper->jsonEncode($postArray);

        $paymentNonce = $this->generateMerchantTransactionId();

        $msg = $apiKey . $paymentNonce . $timestamp . $jsonPayload;
        $messageSignature = base64_encode(hash_hmac('sha256', $msg, $secretKey, true));

        $headers = [
            'Api-Key' => $apiKey,
            'Content-Type' => $contentType,
            'Content-Length' => strlen($jsonPayload),
            'Message-Signature' => $messageSignature,
            'Client-Request-Id' => $paymentNonce,
            'Timestamp' => $timestamp
        ];

        $this->addLog('================Header Start===================');
        $this->addLog($headers, true);
        $this->addLog('================Header End===================');

        try {
            $apiBaseUrl = ($apiUrl != null) ? $apiUrl : $this->getApiBaseUrl($storeId);
            $url = $apiBaseUrl . $path;
            $this->addLog('api url: ' . $url);

            $this->curl->setHeaders($headers);
            $this->curl->setOptions([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 40,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => 1,
            ]);

            if ($type === 'POST') {
                $this->curl->post($url, $jsonPayload);
            } else {
                $this->curl->setOptions([
                    CURLOPT_CUSTOMREQUEST => $type,
                    CURLOPT_POSTFIELDS => $jsonPayload
                ]);
                $this->curl->get($url);
            }

            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            $responseArray['status'] = ($httpCode >= 100 && $httpCode < 300) ? 'success' : 'error';
            $decodedResponse = $this->jsonHelper->jsonDecode($response);
            $responseArray['response'] = $this->arrayToObject($decodedResponse);
        } catch (\Exception $e) {
            $this->addLog('API Request Error: ' . $e->getMessage());
            $responseArray['status'] = 'error';
            $responseArray['code'] = 400;
            $responseArray['response']['message'] = $e->getMessage();
        }

        return $responseArray;
    }

    /**
     * Convert an array to an object recursively
     *
     * @param array $array Array to convert
     * @return object
     */
    private function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $object->$key = $this->arrayToObject($value);
            } else {
                $object->$key = $value;
            }
        }

        return $object;
    }

    /**
     * Get API base URL based on mode
     *
     * @param int|string|null $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getApiBaseUrl($storeId = null)
    {
        if ($this->config->getConfig('payment/basic/lloyds_mode', $storeId) == 'Test') {
            return 'https://cert.api.firstdata.com/gateway/v2/';
        }
        return 'https://prod.api.firstdata.com/gateway/v2/';
    }

    /**
     * Set curl option directly
     *
     * @param int|string $name
     * @param mixed $value
     */
    protected function curlOption($name, $value)
    {
        curl_setopt($this->ch, $name, $value); // phpcs:ignore
    }

    /**
     * Throw error exception
     *
     * @param string $string
     * @return void
     * @throws \Exception
     */
    public function doError($string)
    {
        throw new \InvalidArgumentException($string);
    }

    /**
     * Get payment Token
     *
     * @param string $tokenId
     * @return mixed
     * @throws \Exception
     */
    public function getTokenValue($tokenId)
    {
        $customerId = $this->checkoutSession->getQuote()->getCustomerId();
        try {
            /** @var \AutifyDigital\LloydscardnetPayment\Model\PaymentToken $paymentTokenModel */
            $paymentTokenModel = $this->paymentTokenCollectionFactory->create()
                ->addFieldToFilter('paymenttoken_id', $tokenId)
                ->addFieldToFilter('customer_id', (string) $customerId)
                ->getFirstItem();
            if ($paymentTokenModel->getId()) {
                return $paymentTokenModel->getPaymentToken();
            }
        } catch (\Exception $ex) {
            $this->addLog('PaymentTokenModel Get Token Error: ' . $ex->getMessage());
        }
        return false;
    }

    /**
     * Get admin payment Token
     *
     * @param string $tokenId
     * @param string $email
     * @return mixed
     * @throws \Exception
     */
    public function getAdminTokenValue($tokenId, $email)
    {
        $customerId = $this->findCustomerByEmail($email)->getId();
        try {
            /** @var \AutifyDigital\LloydscardnetPayment\Model\PaymentToken $paymentTokenModel */
            $paymentTokenModel = $this->paymentTokenCollectionFactory->create()
                ->addFieldToFilter('paymenttoken_id', $tokenId)
                ->addFieldToFilter('customer_id', (string) $customerId)
                ->getFirstItem();
            if ($paymentTokenModel->getId()) {
                return $paymentTokenModel->getPaymentToken();
            }
        } catch (\Exception $ex) {
            $this->addLog('PaymentTokenModel Get Token Error: '. $ex->getMessage());
        }
        return false;
    }

    /**
     * Find a customer by email across all websites
     *
     * @param string $email
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function findCustomerByEmail($email)
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('email', $email, 'eq')
                ->create();

            $customers = $this->customerRepository->getList($searchCriteria)->getItems();

            if (!empty($customers)) {
                return reset($customers);
            }
        } catch (\Exception $e) {
            $this->addLog('Error finding customer by email: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get payment Token Data
     *
     * @param int $tokenId
     * @return mixed
     * @throws \Exception
     */
    public function getPaymentTokenData($tokenId)
    {
        try {
            $paymentTokenModel = $this->lcPaymentTokenRepository->get((int) $tokenId);
            return $paymentTokenModel;
        } catch (\Exception $ex) {
            $this->addLog('PaymentTokenModel Get Token Error: ' . $ex->getMessage());
        }
        return false;
    }

    /**
     * Save Payment Token
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    public function savePaymentToken($data)
    {
        try {
            /** @var \AutifyDigital\LloydscardnetPayment\Model\ResourceModel\PaymentToken\Collection $paymentTokenCollection */
            $paymentTokenCollection = $this->paymentTokenCollectionFactory->create()
                ->addFieldToFilter('customer_id', $data['customer_id'])
                ->addFieldToFilter('brand', $data['brand'])
                ->addFieldToFilter('last4', $data['last4']);

            if ($paymentTokenCollection->getSize() > 0) {
                foreach ($paymentTokenCollection as $paymentToken) {
                    $this->lcPaymentTokenRepository->delete($paymentToken);
                }
            }

            $paymentTokenModel = $this->lcPaymentTokenFactory->create();
            $paymentTokenModel->setCustomerId($data['customer_id']);
            $paymentTokenModel->setPaymentToken($data['payment_token']);
            $paymentTokenModel->setMasked($data['masked']);
            $paymentTokenModel->setBrand($data['brand']);
            $paymentTokenModel->setExpMonth($data['exp_month']);
            $paymentTokenModel->setExpYear($data['exp_year']);
            $paymentTokenModel->setData('last4', $data['last4']);
            $isFiserv = isset($data['is_fiserv']) ? $data['is_fiserv'] : 0;
            $paymentTokenModel->setData('is_fiserv', $isFiserv);

            if (isset($data['scheme_transaction_id']) && !empty($data['scheme_transaction_id'])) {
                $paymentTokenModel->setData('scheme_transaction_id', $data['scheme_transaction_id']);
            }

            $this->lcPaymentTokenRepository->save($paymentTokenModel);
        } catch (\Exception $ex) {
            $this->addLog('PaymentTokenModel Save Error: ' . $ex->getMessage());
        }
    }

    /**
     * Get Card Collection By using Customer Id
     *
     * @param string $customerId
     * @param int $isFiserv
     */
    public function getPaymentTokensByCustomerId($customerId, $isFiserv = 0)
    {
        $cardArr = [];
        $currentMonth = $this->dateTime->date('m');
        $currentYear = $this->dateTime->date('Y');

        $paymentTokenCollection = $this->paymentTokenCollectionFactory->create();

        $paymentTokenCollection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('is_fiserv', (string)$isFiserv)
            ->getSelect()
            ->where(
                new Expression(
                    sprintf(
                        'exp_year > %d OR (exp_year = %d AND exp_month > %d)',
                        (int) $currentYear,
                        (int) $currentYear,
                        (int) $currentMonth
                    )
                )
            );

        if ($paymentTokenCollection->getSize() > 0) {
            foreach ($paymentTokenCollection as $paymentToken) {
                $cardArr[] = [
                    'token_id' => $this->encryptor->encrypt($paymentToken->getId()),
                    'masked' => $paymentToken->getMasked(),
                    'brand' => $paymentToken->getBrand(),
                    'scheme_transaction_id' => $paymentToken->getSchemeTransactionId(),
                ];
            }

        }
        return $cardArr;
    }

    /**
     * Get UserAgent
     */
    public function getUserAgent()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        $safariBrowser = false;

        if (preg_match('/MSIE (\d+\.\d+);/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/Chrome[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/Edge\/\d+/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/Firefox[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/OPR[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/Samsung[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/qqbrowser[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = false;
        } elseif (preg_match('/Safari[\/\s](\d+\.\d+)/', $userAgent)) {
            $safariBrowser = true;
        }
        return $safariBrowser;
    }

    /**
     * Make process of refund
     *
     * @param object $payment
     * @param float $amount
     */
    public function doRefund($payment, $amount)
    {
        $order = $payment->getOrder();
        $refundActive = $this->config->getConfig('payment/basic/refund_active', $order->getStoreId());
        // Block only when refunds are explicitly disabled; null/unset defaults to enabled.
        if ($refundActive !== null && (string) $refundActive === '0') {
            throw new LocalizedException(__('Refunds are disabled for Lloyds Cardnet.'));
        }
        $orderId = $order->getId();
        $currency = $order->getOrderCurrencyCode();
        $currency = $this->getIsoCurrencyCodeFromCurrencyCode($currency);
        $amount = number_format((float)$amount, 2, '.', '');

        $orderPayment = $this->getPaymentByOrderId($orderId);

        if (!$orderPayment->getCardnetOrderId()) {
            throw new LocalizedException(
                __(
                    'There is no online Lloyds Cardnet transaction to refund for this order. '
                    . 'If the invoice was created offline, please create an offline credit memo instead.'
                )
            );
        }

        if ($orderPayment->getCardnetOrderId()) {
            $mode = $this->config->getConfig('payment/basic/lloyds_mode', $order->getStoreId());
            $paymentConfig = $this->config->getBasicConfigurations($mode, $order->getStoreId());
            $apiUrl = $paymentConfig['rest_url'];

            $refundEndpoint = 'gateway/v2/orders/' . $orderPayment->getCardnetOrderId();

            $refundRequest = [
                "requestType" => "ReturnTransaction",
                "transactionAmount" => [
                    "total" => $amount,
                    "currency" => $currency,
                ],
            ];
            $this->addLog('Refund request ' . $this->serializer->serialize($refundRequest));
            $response = $this->callCurl($refundEndpoint, $refundRequest, "POST", $apiUrl, $order->getStoreId());
            $this->addLog('Refund response for order ' . $order->getId() . ': ' . $this->serializer->serialize($response));

            if (isset($response['response'])) {
                $responseData = $response['response'];
                $approvalCode = $responseData->approvalCode;
                $transactionStatus = $responseData->transactionStatus;

                try {
                    if ($response['status'] == 'success') {
                        if ($this->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                            $transactionId = $responseData->ipgTransactionId;
                            $payment
                                ->setTransactionId($transactionId)
                                ->setIsTransactionClosed(false);
                            $orderPayment->setData('cardnet_refund_id', $transactionId);
                            $orderPayment->setData('status', 5);
                            $this->paymentsRepository->save($orderPayment);
                            $order->addCommentToStatusHistory(
                                __(
                                    'Online refund of %1 %2 issued by %3. Transaction ID: %4',
                                    $amount,
                                    $order->getOrderCurrencyCode(),
                                    $this->getRefundIssuerName(),
                                    $transactionId
                                )
                            );
                            $this->orderRepository->save($order);

                            return true;
                        }
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * Retrieves checkout details from Fiserv API by given checkout ID
     *
     * @param string $checkoutId
     * @param int|string|null $storeId
     * @return array|null
     */
    public function getCheckoutDetailsById(string $checkoutId, $storeId = null)
    {
        $response = $this->callFiservCurl(Data::FISERV_API_URL."/{$checkoutId}", [], 'GET', $storeId);
        if ($response['status'] == 'success') {
            return $response;
        }

        $this->addLog('Failed to retrieve checkout details for ID: ' . $checkoutId);
        return null;
    }

    /**
     * Process refund request via Fiserv API
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function doRefundFiserv($payment, ?float $amount = null): bool
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();
        $refundActive = $this->config->getConfig('payment/basic/refund_active', $storeId);
        // Block only when refunds are explicitly disabled; null/unset defaults to enabled.
        if ($refundActive !== null && (string) $refundActive === '0') {
            throw new LocalizedException(__('Refunds are disabled for Lloyds Cardnet.'));
        }
        $currency = $order->getOrderCurrencyCode();
        $currency = $this->getIsoCurrencyCodeFromCurrencyCode($currency);
        if ($order->getData('fiserv_checkout_id') === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Refund failed. Order had no valid Fiserv checkout ID.')
            );
        }

        $checkoutId = $order->getData('fiserv_checkout_id');
        $checkoutDetails = $this->serializer->unserialize(
            $this->serializer->serialize($this->getCheckoutDetailsById($checkoutId, $storeId))
        );

        if (!isset($checkoutDetails['transactionStatus']) && isset($checkoutDetails['response']['transactionStatus'])) {
            $checkoutDetails = $checkoutDetails['response'];
        }

        if ($checkoutDetails['transactionStatus'] !== 'APPROVED') {
            throw new \Magento\Framework\Exception\LocalizedException(
                new \Magento\Framework\Phrase('Transaction is non-refundable because status is not APPROVED.')
            );
        }

        $transactionId = $checkoutDetails['ipgTransactionDetails']['ipgTransactionId'];
        $refundAmount = number_format((float)$amount, 2, '.', '');

        $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
        $config = $this->config->getBasicConfigurations($mode, $storeId);

        $refundRequest = [
            "requestType" => "ReturnTransaction",
            "storeId" => $config['store_id'],
            "transactionAmount" => [
                "total" => $refundAmount,
                "currency" => $currency
            ]
        ];
        $response = $this->callCurl(self::PAYMENTS_API_URL."/{$transactionId}", $refundRequest, 'POST', '', $storeId);
        $this->addLog('Refund response for order ' . $order->getId() . ': ' . $this->serializer->serialize($response));

        try{
            if($response['status'] == 'success') {
                $orderPayment = $this->getPaymentByOrderId($order->getId());
                $responseData = $response['response'];
                $approvalCode = $responseData->approvalCode;
                $transactionStatus = $responseData->transactionStatus;
                if ($this->startsWith($approvalCode, 'Y:') && $transactionStatus === 'APPROVED') {
                    $transactionId = $responseData->ipgTransactionId;
                    $payment
                        ->setTransactionId($transactionId)
                        ->setIsTransactionClosed(false);
                    $orderPayment->setData('cardnet_refund_id', $transactionId);
                    $orderPayment->setData('status', 5);
                    $this->paymentsRepository->save($orderPayment);

                    $order->addCommentToStatusHistory(
                        __(
                            'Online refund of %1 %2 issued by %3. Transaction ID: %4',
                            $refundAmount,
                            $order->getOrderCurrencyCode(),
                            $this->getRefundIssuerName(),
                            $transactionId
                        )
                    );
                    $this->orderRepository->save($order);

                    return true;
                }
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage(), true);
        }

        return false;
    }

    /**
     * Get Response Message
     *
     * @param string $responseCode
     */
    public function getResponseMessage($responseCode)
    {
        $response_code_array = [
            '1' => 'Successful authentication (VISA ECI 05, MasterCard ECI 02)',
            '2' => 'Successful authentication without AVV (VISA ECI 05, MasterCard ECI 02)',
            '3' => 'Authentication failed / rejected by the DS or ACS (transaction declined by the Gateway)',
            '4' => 'Authentication attempt (VISA ECI 06, MasterCard ECI 01)',
            '5' => 'Unable to authenticate / DS not responding (VISA ECI 07)',
            '6' => 'Unable to authenticate / ACS or DS are unable to authenticate the cardholder (VISA ECI 07)',
            '7' => 'Cardholder not enrolled for 3-D Secure (VISA ECI 07)',
            '8' => 'Invalid 3-D Secure values received',
            '9' => 'Tokenisation (tokenised PAN, eWallet)'
        ];

        if ($responseCode && array_key_exists($responseCode, $response_code_array)) {
            return $response_code_array[$responseCode];
        }

        return null;
    }

    /**
     * Return AVS Code
     *
     * @param string $approvalCode
     * @return array
     */
    public function getAVSCode($approvalCode)
    {
        $streetMatch = 'N';
        $postalCodeMatch = 'N';
        $cvvMatch = 'N';
        if ($approvalCode && $this->startsWith($approvalCode, 'Y:') &&
            substr_count($approvalCode, ':') >= 3
        ) {
            $encodedStr = explode(':', $approvalCode)[3];
            $avsResponseCode = substr($encodedStr, 0, 3);
            switch ($avsResponseCode) {
                case 'YYY':
                    $streetMatch = 'Y';
                    $postalCodeMatch = 'Y';
                    break;

                case 'YNA':
                    $streetMatch = 'Y';
                    break;

                case 'NYZ':
                    $postalCodeMatch = 'Y';
                    break;

                case 'YPX':
                    $streetMatch = 'Y';
                    break;

                case 'PYX':
                    $postalCodeMatch = 'Y';
                    break;
            }
            if (strlen($encodedStr) == 4) {
                $cvvMatch = ('M' == $encodedStr[3]) ? 'Y' : 'N';
            }
        }
        return [
            'streetMatch' => $streetMatch,
            'postalCodeMatch' => $postalCodeMatch,
            'cvvMatch' => $cvvMatch
        ];
    }

    /**
     * Return Config Value
     *
     * @param string $config
     * @return string
     */
    public function getConfig($config)
    {
        return $this->config->getConfig($config);
    }

    /**
     * Get Card Collection By using Customer Id
     *
     * @param string $customerId
     */
    public function getPaymentTokensAdminByCustomerId($customerId)
    {
        $currentMonth = $this->dateTime->date('m');
        $currentYear = $this->dateTime->date('Y');
        $paymentTokenCollection = $this->paymentTokenCollectionFactory->create();

        $paymentTokenCollection->addFieldToFilter('customer_id', $customerId)
            ->getSelect()
            ->where(
                new Expression(
                    sprintf(
                        'exp_year > %d OR (exp_year = %d AND exp_month > %d)',
                        (int) $currentYear,
                        (int) $currentYear,
                        (int) $currentMonth
                    )
                )
            );

        if ($paymentTokenCollection->getSize() > 0) {
            $cardArr = [];
            foreach ($paymentTokenCollection as $paymentToken) {
                $cardArr[] = [
                    'token_id' => $paymentToken->getId(),
                    'masked' => $paymentToken->getMasked(),
                    'brand' => ucfirst($paymentToken->getBrand()),
                    'scheme_transaction_id' => $paymentToken->getSchemeTransactionId(),
                ];
            }
            return $cardArr;
        }
        return false;
    }

    /**
     * Check if Applepay button should be displayed or not
     *
     * @param float $cartTotal
     * @return bool
     */
    public function displayApplePay($cartTotal)
    {
        $applePayActive = $this->config->getConfig('payment/cardnetapplepay/active');
        if (!$applePayActive) {
            return false;
        }

        if ($this->getApplepayPaymentMethodInteration() === 'hosted' && !$this->getUserAgent()) {
            return false;
        }

        $mode = $this->config->getConfig('payment/basic/lloyds_mode');
        $config = $this->config->getBasicConfigurations($mode);

        if (empty($config['store_id']) || empty($config['api_key']) || empty($config['api_secret'])) {
            return false;
        }

        $minOrderTotal = (float)$this->config->getConfig('payment/cardnetapplepay/min_order_total');
        $maxOrderTotal = (float)$this->config->getConfig('payment/cardnetapplepay/max_order_total');
        $cartTotal = (float)$cartTotal;

        if (($minOrderTotal && $cartTotal < $minOrderTotal) || ($maxOrderTotal && $cartTotal > $maxOrderTotal)) {
            return false;
        }

        return true;
    }

    /**
     * Check if Applepay button should be displayed on the cart page
     *
     * @param float $orderTotal
     * @return bool
     */
    public function displayCartApplePay($orderTotal)
    {
        $coreApplePayVisibility = $this->displayApplePay($orderTotal);
        $cartApplePayVisiblity = $this->config->getConfig('payment/cardnetapplepay/display_cart');
        return $coreApplePayVisibility && $cartApplePayVisiblity;
    }

    /**
     * Check if Applepay button should be displayed on the minicart page
     *
     * @param float $orderTotal
     * @return bool
     */
    public function displayMiniCartApplePay($orderTotal)
    {
        $coreApplePayVisibility = $this->displayApplePay($orderTotal);
        $minicartApplePayVisiblity = $this->config->getConfig('payment/cardnetapplepay/display_minicart');
        return $coreApplePayVisibility && $minicartApplePayVisiblity;
    }

    /**
     * Check if Applepay button should be displayed on the product page
     *
     * @return bool
     */
    public function displayProductApplePay()
    {
        $applePayActive = $this->config->getConfig('payment/cardnetapplepay/active');
        $productApplePayVisiblity = $this->config->getConfig('payment/cardnetapplepay/display_product');

        if ($this->getApplepayPaymentMethodInteration() === 'hosted' && !$this->getUserAgent()) {
            return false;
        }

        return $applePayActive && $productApplePayVisiblity;
    }

    /**
     * Check if Google Pay button should be displayed on the checkout page
     *
     * @param float $cartTotal
     * @return bool
     */
    public function displayGooglePay($cartTotal)
    {

        $googlePayActive = $this->config->getConfig('payment/cardnetgooglepay/active');
        if (!$googlePayActive) {
            return false;
        }

        $mode = $this->config->getConfig('payment/basic/lloyds_mode');
        $config = $this->config->getBasicConfigurations($mode);

        $store_id = $config['store_id'] ?? '';
        $api_key = $config['api_key'] ?? '';
        $api_secret = $config['api_secret'] ?? '';

        if (empty($store_id) || empty($api_key) || empty($api_secret)) {
            return false;
        }

        $minOrderTotal = (float) $this->config->getConfig('payment/cardnetgooglepay/min_order_total');
        $maxOrderTotal = (float) $this->config->getConfig('payment/cardnetgooglepay/max_order_total');
        $cartTotal = (float) $cartTotal;

        if (($minOrderTotal && $cartTotal < $minOrderTotal) ||
            ($maxOrderTotal && $cartTotal > $maxOrderTotal)) {
            return false;
        }

        return true;
    }

    /**
     * Check if google pay button should be displayed on the cart page
     *
     * @param float $orderTotal
     * @return bool
     */
    public function displayCartGooglePay($orderTotal)
    {
        $coreGooglePayVisibility = $this->displayGooglePay($orderTotal);
        $cartGooglePayVisiblity = $this->config->getConfig('payment/cardnetgooglepay/display_cart');
        return $coreGooglePayVisibility && $cartGooglePayVisiblity;
    }

    /**
     * Check if google pay button should be displayed on the mini cart
     *
     * @param float|int|string|null $ordeTotal
     * @return bool
     */
    public function displayMiniCartGooglePay(float|int|string|null $ordeTotal = null)
    {
        $coreGooglePayVisibility = $this->displayGooglePay((float) $ordeTotal);
        $minicartGooglePayVisiblity = $this->config->getConfig('payment/cardnetgooglepay/display_minicart');
        return $coreGooglePayVisibility && $minicartGooglePayVisiblity;
    }

    /**
     * Check if google pay button should be displayed on the product page
     *
     * @return bool
     */
    public function displayProductGooglePay()
    {
        $googlePayActive = $this->config->getConfig('payment/cardnetgooglepay/active');
        $productGooglePayVisiblity = $this->config->getConfig('payment/cardnetgooglepay/display_product');
        return $googlePayActive && $productGooglePayVisiblity;
    }

    /**
     * Returns a URL for the given route
     *
     * @param string $url Route name
     * @param array $params Route parameters
     * @return string URL
     */
    public function getUrl($url, $params = [])
    {
        return $this->urlBuilder->getUrl($url, $params);
    }

    /**
     * Get store locale code
     *
     * @return string
     */
    public function getLocale()
    {
        $localeCode = $this->scopeConfig->getValue(
            'general/locale/code',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $supportedLocales = [
            'en_US' => 'en_US',
            'en_GB' => 'en_GB',
            'de_DE' => 'de_DE',
            'de_PL' => 'de_PL',
            'nl_NL' => 'nl_NL'
        ];

        return $supportedLocales[$localeCode] ?? 'en_GB';
    }

    /**
     * Call Fiserv Curl
     *
     * @param string $path
     * @param array $postArray
     * @param string $type
     * @param int|string|null $storeId
     * @return array
     * // @codingStandardsIgnoreStart
     */
    public function callFiservCurl($path, $postArray = [], $type = 'POST', $storeId = null)
    {
        $responseArray = [];
        $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
        $config = $this->config->getBasicConfigurations($mode, $storeId);

        $secretKey = $config['api_secret'];
        $apiKey = $config['api_key'];
        $nonce = time() * 1000 + rand();
        $timestamp = time() * 100000;

        $contentType = 'application/json';
        $jsonPayload = $this->jsonHelper->jsonEncode($postArray);
        $out = bin2hex(random_bytes(18));
        $out[8] = "-";
        $out[13] = "-";
        $out[18] = "-";
        $out[23] = "-";
        $out[14] = "4";
        $out[19] = ["8", "9", "a", "b"][random_int(0, 3)];
        $paymentNonce = $out;

        $msg = ($type === 'GET')
            ? $apiKey . $paymentNonce . $timestamp
            : $apiKey . $paymentNonce . $timestamp . $jsonPayload;

        $messageSignature = base64_encode(hash_hmac('sha256', $msg, strval($secretKey), true));

        $headers = [
            'Api-Key: ' . $apiKey,
            'Content-Type: ' . $contentType,
            'Message-Signature: ' . $messageSignature,
            'Client-Request-Id: ' . $paymentNonce,
            'Timestamp: ' . $timestamp
        ];

        // Only add Content-Length for non-GET requests
        if ($type !== 'GET') {
            $headers[] = 'Content-Length: ' . strlen($jsonPayload);
        }

        $this->addLog('================Header Start===================');
        $this->addLog($headers, true);
        $this->addLog('================Header End===================');

        try {
            $apiBaseUrl = $this->getApiBaseUrlLBOP(null, $storeId);
            $url = $apiBaseUrl . 'exp/v1/' . $path;

            // Append query parameters to URL for GET requests
            if ($type === 'GET' && !empty($postArray)) {
                $url .= '?' . http_build_query($postArray);
            }

            $this->addLog('api url: ' . $url);

            $curl = curl_init();

            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => 1,
                CURLOPT_CUSTOMREQUEST => $type,
                CURLOPT_HTTPHEADER => $headers,
            ];

            // Only add request body for non-GET requests
            if ($type !== 'GET') {
                $curlOptions[CURLOPT_POSTFIELDS] = $jsonPayload;
            }

            curl_setopt_array($curl, $curlOptions);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);

            if ($httpCode === 200 || $httpCode === 201 || $httpCode === 202 || $httpCode === 203 || $httpCode === 204) {
                $responseArray['status'] = 'success';
            } else {
                $responseArray['status'] = 'error';
            }

            if ($response) {
                $responseArray['response'] = $this->jsonHelper->jsonDecode($response);
            } else {
                $responseArray['response'] = 'Error';
            }
        } catch (\Exception $e) {
            $responseArray['status'] = 'error';
            $responseArray['code'] = 400;
            $responseArray['response']['message'] = $e->getMessage();
        }
        return $responseArray;
    }

    /**
     * Get API base URL for LBOP API
     *
     * @param string|null $mode Explicit mode override ('Test' or 'Live'). When null, uses saved config.
     * @param int|string|null $storeId
     * @return string
     */
    public function getApiBaseUrlLBOP(?string $mode = null, $storeId = null)
    {
        $resolvedMode = $mode ?? $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
        if ($resolvedMode == 'Test') {
            return 'https://prod.emea.api.fiservapps.com/sandbox/';
        }
        return 'https://prod.emea.api.fiservapps.com/';
    }

    /**
     * Encodes a given PHP variable into a JSON string.
     *
     * @param mixed $data
     * @return string
     */
    public function getJsonEncode($data)
    {
        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Decodes a given JSON string into a PHP variable.
     *
     * @param string $data
     * @return mixed
     */
    public function getJsonDecode($data)
    {
        return $this->jsonHelper->jsonDecode($data);
    }

    /**
     * Returns the library to be used for Lloyd's Cardnet payment processing.
     * If the 'library' configuration value is set, it is used; otherwise, the 'lloyds_mode' configuration value is used.
     *
     * @return string
     */
    public function getLibrary()
    {
        $mode = $this->getMode();
        if ($mode == 'Test') {
            return $this->config->getConfig('payment/basic/library_uat') ?: trim($this->config->getConfig('payment/basic/library_uat'));
        }
        return $this->config->getConfig('payment/basic/library_live') ?: trim($this->config->getConfig('payment/basic/library_live'));
    }

    /**
     * Generates a random merchant transaction ID.
     *
     * The merchant transaction ID is a Universally Unique Identifier (UUID) formatted as a 32-digit hexadecimal string.
     * It is used to identify a transaction and is required for all calls to the Gateway.
     *
     * @return string
     */
    /**
     * Get order ID with configured timestamp suffix appended
     *
     * @param string $incrementId
     * @return string
     */
    public function getOrderIdWithSuffix($incrementId)
    {
        $enabled = (int) $this->config->getConfig('payment/basic/order_id_suffix');
        if ($enabled === 1) {
            return $incrementId . '-' . time();
        }
        return $incrementId;
    }

    /**
     * Strip timestamp suffix from order ID to get the original increment ID
     *
     * @param string $orderId
     * @return string
     */
    public function stripOrderIdSuffix($orderId)
    {
        if ($orderId === null || $orderId === '') {
            return (string) $orderId;
        }
        $orderId = (string) $orderId;
        if (preg_match('/^(.+)-\d{10}$/', $orderId, $matches)) {
            return $matches[1];
        }
        return $orderId;
    }

    public function generateMerchantTransactionId()
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(16384, 20479),
            random_int(32768, 49151),
            random_int(0, 65535),
            random_int(0, 65535),
            random_int(0, 65535)
        );
    }

    /**
     * Get payment method icon URL
     *
     * @param string $paymentMethod
     * @return string
     */
    public function getPaymentMethodIcon($paymentMethod)
    {
        $iconMap = [
            'visa' => 'Magento_Payment::images/cc/vi.png',
            'mastercard' => 'Magento_Payment::images/cc/mc.png',
            'american-express' => 'Magento_Payment::images/cc/ae.png',
            'discover' => 'Magento_Payment::images/cc/di.png',
            'jcb' => 'Magento_Payment::images/cc/jcb.png',
            'maestro' => 'Magento_Payment::images/cc/sm.png',
            'diners-club' => 'Magento_Payment::images/cc/dc.png',
            'unionpay' => 'Magento_Payment::images/cc/un.png',
            'electron' => 'Magento_Payment::images/cc/vi.png',
            'mir' => 'Magento_Payment::images/cc/mir.png',
            'elo' => 'Magento_Payment::images/cc/elo.png'
        ];

        $method = strtolower($paymentMethod);

        if (isset($iconMap[$method])) {
            return $this->assetRepo->getUrl($iconMap[$method]);
        }

        return 'N/A';
    }

    /**
     * Get a human-readable name for a payment method code
     *
     * @param string $methodCode
     * @return string
     */
    public function getPaymentMethod($methodCode)
    {
        switch ($methodCode) {
            case 'lcnetredirect':
                return 'HPP Redirect Payment';
            case 'lcnetpaymentjs':
                return 'LLoyds Cardnet PaymentJS';
            case 'cardnetapplepay':
                return 'Cardnet Apple Pay';
            case 'cardnetgooglepay':
                return 'Cardnet Google Pay';
            case 'lbopcheckoutsolution':
                return 'Lloyds Checkout Solution';
            default:
                return $methodCode;
        }
    }

    /**
     * Get remote status CSS class
     *
     * @param string $status
     * @return string
     */
    public function getRemoteStatusClass($status)
    {
        $status = strtolower($status);
        if ($status == 'passed' || $status == 'approved' || $status == 'success') {
            return 'remote-status-passed';
        } elseif ($status == 'waiting' || $status == 'pending') {
            return 'remote-status-waiting';
        } else {
            return 'remote-status-failed';
        }
    }

    /**
     * Get check result HTML
     *
     * @param string $result
     * @return string
     */
    public function getCheckResult($result)
    {
        switch (strtoupper($result)) {
            case 'Y':
                return '<span class="check-pass">' . __('Pass ✓') . '</span>';
            case 'N':
                return '<span class="check-fail">' . __('Fail ✗') . '</span>';
            default:
                return '<span class="check-unchecked">' . __('Unchecked') . '</span>';
        }
    }

    /**
     * Get status CSS class
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass($status)
    {
        switch ($status) {
            case '1':
                return 'status-pending';
            case '2':
                return 'status-success';
            case '3':
                return 'status-cancelled';
            case '4':
                return 'status-error';
            case '5':
                return 'status-refunded';
            default:
                return 'status-unknown';
        }
    }

    /**
     * Get Refund Amount
     *
     * @param int $orderId
     * @return float
     */
    public function getRefundAmount($orderId)
    {
        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            if ($order && $order->getEntityId()) {
                return (float) $order->getTotalRefunded();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return 0.00;
        } catch (\Exception $e) {
            return 0.00;
        }
        return 0.00;
    }

    /**
     * Return Applepay button color
     *
     * @return string
     */
    public function getApplePayButtonColor()
    {
        return $this->config->getConfig('payment/cardnetapplepay/button_color');
    }

    /**
     * Get Payment Method Interaction
     *
     * @return string
     */
    public function getGooglepayPaymentMethodInteration()
    {
        return $this->config->getConfig('payment/cardnetgooglepay/payment_method_integration');
    }

    /**
     * Get Payment Method Interaction
     *
     * @return string
     */
    public function getApplepayPaymentMethodInteration()
    {
        return $this->config->getConfig('payment/cardnetapplepay/payment_method_integration');
    }

    /*
     * Get Payment By IPG Transaction Id
     *
     * @param int $ipgTransactionId
     * @return mixed
     */
    public function getPaymentByIPGTransactionId($ipgTransactionId)
    {
        $payment = $this->paymentsCollectionFactory->create()
            ->addFieldToFilter('ipgTransactionId', $ipgTransactionId)
            ->getFirstItem(); // phpcs:ignore

        return $payment;
    }

    /**
     * Fetch transaction info by IPG transaction id
     *
     * @param string $ipgTransactionId
     * @param int|string|null $storeId
     * @return array
     */
    public function fetchTransactionInfo($ipgTransactionId, $storeId = null)
    {
        $mode = $this->config->getConfig('payment/basic/lloyds_mode', $storeId);
        $config = $this->config->getBasicConfigurations($mode, $storeId);
        $secretKey = $config['api_secret'];
        $apiKey = $config['api_key'];

        $timestamp = (int)(microtime(true) * 1000);
        $clientRequestId = $this->generateMerchantTransactionId();
        $msg = $apiKey . $clientRequestId . $timestamp;
        $messageSignature = base64_encode(hash_hmac('sha256', $msg, $secretKey, true));

        $headers = [
            'Content-Type'       => 'application/json',
            'Api-Key'            => $apiKey,
            'Client-Request-Id'  => $clientRequestId,
            'Timestamp'          => $timestamp,
            'Message-Signature'  => $messageSignature,
            'Accept'             => 'application/json'
        ];
        $this->curl->setHeaders($headers);

        $baseUrl = ($mode === 'Test')
            ? 'https://prod.emea.api.fiservapps.com/sandbox/ipp/payments-gateway/v2/payments/'
            : 'https://prod.emea.api.fiservapps.com/ipp/payments-gateway/v2/payments/';
        $url = $baseUrl . urlencode($ipgTransactionId);

        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_TIMEOUT, 40);
        $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->get($url);

        $response = $this->curl->getBody();
        $httpCode = $this->curl->getStatus();

        return ['response' => $response, 'httpCode' => $httpCode];

    }

    /**
     * Get license key for specific website
     *
     * @param int|string|null $websiteId
     * @return string|null
     */
    public function getLicenseKey(int|string|null $websiteId = null)
    {
        $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
        if ($websiteId === null) {
            return $this->config->getConfig('payment/lloyds/license_key');
        }
        
        return $this->scopeConfig->getValue(
            'payment/lloyds/license_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );
    }

    /**
     * Get domain for specific website
     *
     * @param int|string|null $websiteId
     * @return string
     */
    protected function getDomain(int|string|null $websiteId = null)
    {
        $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
        if ($websiteId === null) {
            $store = $this->config->getStoreUrl();
            return parse_url($store, PHP_URL_HOST);
        }
        
        try {
            $website = $this->storeManager->getWebsite($websiteId);
            $defaultGroupId = $website->getDefaultGroupId();
            if ($defaultGroupId === null) {
                $this->addLog('No default store group for website ' . $websiteId);
                return parse_url($this->config->getStoreUrl(), PHP_URL_HOST);
            }
            $defaultGroup = $this->storeManager->getGroup($defaultGroupId);
            $defaultStore = $this->storeManager->getStore($defaultGroup->getDefaultStoreId());
            $storeUrl = $defaultStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            return parse_url($storeUrl, PHP_URL_HOST);
        } catch (\Exception $e) {
            $this->addLog('Error getting domain for website ' . $websiteId . ': ' . $e->getMessage());
            return parse_url($this->config->getStoreUrl(), PHP_URL_HOST);
        }
    }

    /**
     * Send request to license API
     *
     * @param array $data
     */
    protected function sendRequest(array $data)
    {
        try {
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->addHeader('User-Agent', 'Lloydscardnetpayment-Magento/' . $this->getModuleVersion());
            $this->curl->setTimeout(15);
            $this->curl->post(self::API_URL, $this->jsonHelper->jsonEncode($data));

            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                $this->addLog('AutifyDigital License API returned HTTP ' . $httpCode);
            }
            
            return $this->jsonHelper->jsonDecode($response);
            
        } catch (\Exception $e) {
            $this->addLog('AutifyDigital License API Request Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Activate module license for specific website
     *
     * @param int|string|null $websiteId
     * @return array
     */
    public function activate(int|string|null $websiteId = null)
    {
        try {
            $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
            $domain = $this->getDomain($websiteId);
            
            $data = [
                'domain' => $domain,
                'website_id' => $websiteId,
                'plugin_name' => $this->getModuleName(),
                'version' => $this->getModuleVersion(),
                'activation_date' => $this->dateTime->date('Y-m-d'),
                'status' => 'active',
                'email' => $this->config->getConfig('trans_email/ident_general/email'),
                'contact_name' => $this->config->getConfig('trans_email/ident_general/name'),
                'site_url' => $domain
            ];

            $response = $this->sendRequest($data);

            if ($response && isset($response['success']) && $response['success']) {
                if (isset($response['license_key'])) {
                    $this->saveLicenseKey($response['license_key'], $websiteId);
                }
                
                $this->addLog('AutifyDigital License: Module activated successfully '. 
                    'domain : ' . $domain .
                    'website_id : ' . $websiteId .
                    'license_key : ' . $response['license_key'] ?? 'N/A'
                , true);
                
                return [
                    'success' => true,
                    'license_key' => $response['license_key'] ?? null,
                    'message' => 'Module activated successfully',
                    'website_id' => $websiteId,
                    'domain' => $domain
                ];
            }
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            $this->addLog('AutifyDigital License Activation Error: ' . $e->getMessage(), true);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get the module name, appending Hyva if the Hyva Checkout module is active
     *
     * @return string
     */
    protected function getModuleName()
    {
        if ($this->moduleList->has('AutifyDigital_LloydscardnetPaymentHyvaCheckout')) {
            return self::MODULE_NAME . ' Hyva';
        }
        return self::MODULE_NAME;
    }

    /**
     * Deactivate module license for specific website
     *
     * @param int|string|null $websiteId
     * @return array
     */
    public function deactivate(int|string|null $websiteId = null)
    {
        try {
            $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
            $domain = $this->getDomain($websiteId);
            $licenseKey = $this->getLicenseKey($websiteId);
            
            $data = [
                'domain' => $domain,
                'website_id' => $websiteId,
                'plugin_name' => $this->getModuleName(),
                'version' => $this->getModuleVersion(),
                'license_key' => $licenseKey,
                'activation_date' => $this->dateTime->date('Y-m-d'),
                'status' => 'deactivated',
                'email' => $this->config->getConfig('trans_email/ident_general/email'),
                'contact_name' => $this->config->getConfig('trans_email/ident_general/name'),
                'site_url' => $domain
            ];

            $response = $this->sendRequest($data);

            if ($response && isset($response['success']) && $response['success']) {
                $this->removeLicenseKey($websiteId);
                
                $this->addLog('AutifyDigital License: Module deactivated successfully'.
                    'domain ' . $domain .
                    'website_id ' . $websiteId
                , true);
                
                return [
                    'success' => true,
                    'message' => 'Module deactivated successfully'
                ];
            }
            
            $this->removeLicenseKey($websiteId);
            
            return [
                'success' => false,
                'message' => $response['message'] ?? 'Deactivation completed locally, but API response failed'
            ];

        } catch (\Exception $e) {
            $this->addLog('AutifyDigital License Deactivation Error: ' . $e->getMessage(), true);
            
            try {
                $this->removeLicenseKey($websiteId);
            } catch (\Exception $removeException) {
                $this->addLog('Failed to remove license key: ' . $removeException->getMessage());
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Save license key to specific scope
     *
     * @param string $licenseKey
     * @param int|string|null $websiteId
     */
    protected function saveLicenseKey(string $licenseKey, int|string|null $websiteId = null)
    {
        try {
            $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
            if ($websiteId === null) {
                $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = 0;
            } else {
                $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
                $scopeId = $websiteId;
            }
            
            $this->configWriter->save(
                'payment/lloyds/license_key',
                $licenseKey,
                $scope,
                $scopeId
            );
        } catch (\Exception $e) {
            $this->addLog('Error saving license key to database: ' . $e->getMessage(), true);
            throw $e;
        }
    }

    /**
     * Remove license key from specific scope
     *
     * @param int|string|null $websiteId
     * @return void
     */
    protected function removeLicenseKey(int|string|null $websiteId = null)
    {
        try {
            $websiteId = ($websiteId === null || $websiteId === '') ? null : (int) $websiteId;
            if ($websiteId === null) {
                $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = 0;
            } else {
                $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES;
                $scopeId = $websiteId;
            }
            
            $this->configWriter->delete(
                'payment/lloyds/license_key',
                $scope,
                $scopeId
            );
                        
            $this->addLog('License key removed successfully from database'. 
                'scope' . $scope .
                'scope_id' . $scopeId
            , true);
        } catch (\Exception $e) {
            $this->addLog('Error removing license key from database: ' . $e->getMessage(), true);
            throw $e;
        }
    }

    /**
     * Get the module version from module.xml
     *
     * If the module version is available from module.xml, it will be returned.     *
     * @return string
     */
    protected function getModuleVersion()
    {
        $moduleInfo = $this->moduleList->getOne('AutifyDigital_LloydscardnetPayment');
        if ($moduleInfo && isset($moduleInfo['setup_version'])) {
            return $moduleInfo['setup_version'];
        }
        return '3.0.15';
    }

    /**
     * Get payment token from vault table using client_token
     *
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return string|null
     */
    public function getPaymentTokenFromVault(\Magento\Sales\Model\Order\Payment $payment): ?string
    {
        try {
            $order = $payment->getOrder();
            $quoteId = $order->getQuoteId();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('payment_method_code', 'lcnetpaymentjs')
                ->addFilter('is_active', 1)
                ->addFilter('is_visible', 1)
                ->create();

            $paymentTokens = $this->paymentTokenRepository->getList($searchCriteria);

            foreach ($paymentTokens->getItems() as $paymentToken) {
                $detailsJson = $paymentToken->getTokenDetails();
                $details = $detailsJson ? $this->serializer->unserialize($detailsJson) : [];

                if ($quoteId && isset($details['quote_id']) && (int)$details['quote_id'] === (int)$quoteId) {
                    return $details['paymentjs_token']['token']
                        ?? $paymentToken->getGatewayToken();
                }

                $clientToken = $payment->getAdditionalInformation('client_token')
                            ?? $payment->getAdditionalInformation('paymentjs_client_token')
                            ?? $payment->getAdditionalInformation('client-token');

                if ($clientToken && isset($details['client_token']) && $details['client_token'] === $clientToken) {
                    return $details['paymentjs_token']['token'] ?? null;
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->autifyLcLogger->error('Error fetching payment token from vault: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update payment record status
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \stdClass $response
     * @return void
     */
    public function updatePaymentRecord($order, $response): void
    {
        try {
            $collection = $this->paymentsCollectionFactory->create()
                ->addFieldToFilter('order_increment_id', (string)$order->getIncrementId())
                ->addFieldToFilter('status', '1')
                ->setPageSize(1);

            /** @var \AutifyDigital\LloydscardnetPayment\Model\Payments $payment */
            $payment = $collection->getFirstItem();

            if ($payment->getId()) {
                $payment->setStatus('2');
                $payment->setIpgTransactionId($response->ipgTransactionId);

                if (isset($response->approvalCode, $response->transactionStatus)) {
                    $payment->setRemoteMessage($response->approvalCode . '|' . $response->transactionStatus);
                }

                $this->paymentsRepository->save($payment);
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage(), true);
        }
    }

    /**
     * Build the form_data payload for a 3DS challenge so it is submitted via the
     * same-origin ChallengeFrame controller instead of POSTing the cross-origin
     * bank ACS URL directly from the checkout page (which the static checkout CSP
     * "form-action" policy blocks). The ChallengeFrame controller dynamically
     * whitelists the ACS host and renders the auto-submitting form server-side.
     *
     * The ACS URL, cReq and TermURL are bundled into a single encrypted blob so
     * the client cannot tamper with where the challenge is submitted.
     *
     * @param string $acsURL
     * @param string $cReq
     * @param string $termURL
     * @return array<string, mixed>
     */
    public function buildChallengeFormData(string $acsURL, string $cReq, string $termURL): array
    {
        $blob = $this->encryptor->encrypt($this->serializer->serialize([
            'acsURL' => $acsURL,
            'creq' => $cReq,
            'TermURL' => $termURL,
        ]));

        return [
            'action' => $this->urlBuilder->getUrl('lloyds/paymentjs/challengeFrame', ['_secure' => true]),
            'fields' => [
                'd' => $blob,
            ],
        ];
    }
}

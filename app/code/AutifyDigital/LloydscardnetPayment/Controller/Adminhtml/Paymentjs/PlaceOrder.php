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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Paymentjs;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Controller\Adminhtml\Order\Create as CreateAction;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Exception\PaymentException;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Backend\Model\Session\Quote as SessionQuote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Phrase;

class PlaceOrder extends CreateAction implements HttpPostActionInterface
{

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var SessionQuote
     */
    private $sessionQuote;

    /**
     * @param Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CartRepositoryInterface $quoteRepository
     * @param SessionQuote $sessionQuote
     */
    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper,
        Config $config,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CartRepositoryInterface $quoteRepository,
        SessionQuote $sessionQuote
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->sessionQuote = $sessionQuote;
        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory
        );
    }

    /**
     * Saving quote and create order
     *
     * @return ResultInterface
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function execute(): ResultInterface
    {
        $this->helper->addLog('Admin Place Order Controller Called');
        $resultJson = $this->resultJsonFactory->create();
        $path = 'sales/*/';
        $pathParams = [];
        $returnData = ['error' => false, 'message' => ''];
        $order_id = null;

        try {
            $request = $this->getRequest();
            if (!$request instanceof HttpRequest) {
                return $this->getErrorResponse('Invalid request type');
            }
            
            $paymentData = $request->getPost('payment');
            if (!empty($paymentData['token_id'])) {
                $token_id = $paymentData['token_id'];
            } elseif (!empty($paymentData['token'])) {
                $token_id = $paymentData['token'];
            } elseif (!empty($paymentData['client_token'])) {
                $token_id = $paymentData['client_token'];
            }
            
            if (empty($token_id)) {
                $message = 'Payment token is not provided or invalid';
                return $this->getErrorResponse($message);
            }
            
            $this->helper->addLog('Payment token received: ' . $token_id);

            $this->helper->addLog('Checking session and quote');
            $sessionQuoteId = $this->sessionQuote->getQuoteId();
            $this->helper->addLog('Session Quote ID: ' . ($sessionQuoteId ?: 'null'));
            
            if (!$sessionQuoteId) {
                $store = $this->storeManager->getStore();
                $quote = $this->_getOrderCreateModel()->initRuleData()->getQuote();
                $quote->setStoreId($store->getId());
                
                $customerEmail = $request->getPost('customer_email');
                if ($customerEmail) {
                    $quote->setCustomerEmail($customerEmail);
                }
                
                $this->quoteRepository->save($quote);
                $this->sessionQuote->setQuoteId($quote->getId());
                $this->helper->addLog('Created new quote with ID: ' . $quote->getId());
            }

            try {
                $quote = $this->_getOrderCreateModel()->getQuote();
                $quoteId = $quote->getId();
                $this->helper->addLog('Order create model quote ID: ' . ($quoteId ?: 'null'));
                
                if (!$quoteId || !$quote->getItemsCount()) {
                    $this->helper->addLog('Loading quote from repository with ID: ' . $sessionQuoteId);
                    $repositoryQuote = $this->quoteRepository->get($sessionQuoteId);
                    
                    // Convert CartInterface to Quote for compatibility
                    if ($repositoryQuote instanceof Quote) {
                        $quote = $repositoryQuote;
                    } else {
                        // If we can't get a Quote instance, create error
                        $message = 'Unable to load quote as Quote instance';
                        $this->helper->addLog('Quote loading failed: ' . $message);
                        return $this->getErrorResponse($message);
                    }
                    
                    $this->_getOrderCreateModel()->setQuote($quote);
                }
                
                if (!$quote->getId() || !$quote->getItemsCount()) {
                    $message = 'The quote is not available or contains no items';
                    $this->helper->addLog('Quote validation failed: ' . $message);
                    return $this->getErrorResponse($message);
                }
                
                $this->helper->addLog('Quote successfully loaded. Items count: ' . $quote->getItemsCount());
            } catch (\Exception $e) {
                $this->helper->addLog('Error loading quote: ' . $e->getMessage());
                return $this->getErrorResponse('Failed to load active quote: ' . $e->getMessage());
            }
            
            if (!$quote->getStoreId()) {
                $storeId = $this->storeManager->getStore()->getId();
                $this->helper->addLog('Setting store ID on quote: ' . $storeId);
                $quote->setStoreId($storeId);
            }

            $orderData = $request->getPost('order');
            $customerEmail = isset($orderData['account']['email']) ? $orderData['account']['email'] : null;
            
            if ($customerEmail && $quote instanceof Quote) {
                $this->helper->addLog('Customer email from order data: ' . $customerEmail);
                
                try {
                    // Check if customer exists across websites
                    $existingCustomer = $this->findCustomerByEmail($customerEmail);
                    
                    if ($existingCustomer) {
                        $this->helper->addLog('Found existing customer with ID: ' . $existingCustomer->getId());
                        $quote->setCustomerId($existingCustomer->getId());
                        $quote->setCustomerEmail($existingCustomer->getEmail());
                        $quote->setCustomerFirstname($existingCustomer->getFirstname());
                        $quote->setCustomerLastname($existingCustomer->getLastname());
                        $quote->setCustomerIsGuest(false);
                        $quote->setCheckoutMethod('');
                        $this->_getSession()->setCustomerId($existingCustomer->getId());
                    } else {
                        // Handle as guest if customer doesn't exist
                        $this->helper->addLog('No existing customer found. Creating as guest or new customer.');
                        $quote->setCustomerEmail($customerEmail);
                        if (!$this->_getSession()->getCustomerId()) {
                            $quote->setCustomerIsGuest(true);
                            $quote->setCheckoutMethod('guest');
                        }
                    }
                    
                    $this->quoteRepository->save($quote);
                    
                } catch (NoSuchEntityException $e) {
                    $this->helper->addLog('Customer not found, continuing as guest or new customer');
                    // Customer doesn't exist, continue as normal
                    $quote->setCustomerEmail($customerEmail);
                    $quote->setCustomerIsGuest(true);
                    $quote->setCheckoutMethod('guest');
                    $this->quoteRepository->save($quote);
                }
            }
    
            if (!$quote->getCustomerIsGuest() &&
                !$this->_authorization->isAllowed('Magento_Customer::manage') &&
                !$this->_getSession()->getCustomerId() &&
                !$quote->getCustomerId()
            ) {
                $message = 'Customer is not allowed to create order';
                $this->helper->addLog('Customer permission check failed: ' . $message);
                return $this->getErrorResponse($message);
            }

            $this->helper->addLog('Processing action data');
            $this->_processActionData('save');
            
            if (is_array($paymentData)) {
                $this->helper->addLog('Setting payment data');
                $paymentData['checks'] = [
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
                ];
                
                if (!isset($paymentData['method_used'])) {
                    $paymentData['method_used'] = 'lcnetpaymentjs';
                }
                
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }

            $this->helper->addLog('Creating order');
            $orderPostData = $request->getPost('order');
            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($orderPostData)
                ->createOrder();

            $this->_getSession()->clearStorage();
            $this->messageManager->addSuccessMessage(__('You created the order.')->render());
            
            if ($this->_authorization->isAllowed('Magento_Sales::actions_view')) {
                $order_id = ['order_id' => $order->getId()];
            }
            
            $this->helper->addLog('Order created successfully. Order ID: ' . $order->getId());
            
        } catch (PaymentException $e) {
            $this->helper->addLog('Payment Exception: ' . $e->getMessage());
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addErrorMessage($message);
                return $this->getErrorResponse($message);
            }
        } catch (LocalizedException $e) {
            $this->helper->addLog('Localized Exception: ' . $e->getMessage());
            // Handle the duplicate email error specifically
            if (strpos($e->getMessage(), 'A customer with the same email address already exists') !== false) {
                $this->helper->addLog('Handling duplicate email error specifically');
                
                try {
                    // Save the quote but don't try to create a new customer
                    $quote = $this->_getOrderCreateModel()->getQuote();
                    if ($quote instanceof Quote) {
                        $quote->setCustomerIsGuest(true);
                        $quote->setCheckoutMethod('guest');
                        $this->quoteRepository->save($quote);
                    }
                    
                    // Try again with customer as guest
                    $this->helper->addLog('Retrying order creation as guest');
                    $orderPostData = $request->getPost('order');
                    $order = $this->_getOrderCreateModel()
                        ->setIsValidate(true)
                        ->importPostData($orderPostData)
                        ->createOrder();
                    
                    $this->_getSession()->clearStorage();
                    $this->messageManager->addSuccessMessage(__('You created the order.')->render());
                    
                    if ($this->_authorization->isAllowed('Magento_Sales::actions_view')) {
                        $order_id = ['order_id' => $order->getId()];
                    }
                    
                    $this->helper->addLog('Order created successfully as guest. Order ID: ' . $order->getId());
                } catch (\Exception $retryException) {
                    $this->helper->addLog('Order retry failed: ' . $retryException->getMessage());
                    return $this->getErrorResponse($retryException->getMessage());
                }
            } else {
                // customer can be created before place order flow is completed and should be stored in current session
                $this->_getSession()->setCustomerId((int)$this->_getSession()->getQuote()->getCustomerId());
                $message = $e->getMessage();
                if (!empty($message)) {
                    $this->messageManager->addErrorMessage($message);
                    return $this->getErrorResponse($message);
                }
            }
        } catch (\Exception $e) {
            $this->helper->addLog('General Exception: ' . $e->getMessage());
            $message = 'Order saving error: ' . $e->getMessage();
            $this->messageManager->addExceptionMessage($e, $message);
            return $this->getErrorResponse($message);
        }
        
        if ($order_id) {
            $this->helper->addLog('Returning success response with order ID: ');
            $this->helper->addLog($this->helper->getJsonEncode($order_id));
        }
        
        return $this->resultJsonFactory->create()->setData(
            [
                'success' => true,
                'order_id' => $order_id,
                'error_messages' => ''
            ]
        );
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
            $this->helper->addLog('Error finding customer by email: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get error response.
     *
     * @param string $msg
     * @return Json
     */
    private function getErrorResponse(string $msg): Json
    {
        if (empty($msg)) {
            $msg = 'Your payment has been declined. Please try again.';
        }
        $this->helper->addLog('Returning error response: ' . $msg);
        return $this->resultJsonFactory->create()->setData(
            [
                'success' => false,
                'error' => true,
                'url' => '',
                'error_messages' => $msg
            ]
        );
    }
}

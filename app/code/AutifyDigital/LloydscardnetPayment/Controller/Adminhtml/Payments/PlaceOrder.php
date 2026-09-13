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

namespace AutifyDigital\LloydscardnetPayment\Controller\Adminhtml\Payments;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Controller\Adminhtml\Order\Create as CreateAction;
use AutifyDigital\LloydscardnetPayment\Helper\Data;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin controller: place order using LCNet payment flow.
 *
 * Handles creation of order from admin Create Order page.
 */
class PlaceOrder extends CreateAction implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     *
     * @var \AutifyDigital\LloydscardnetPayment\Helper\Data
     */
    protected $helper;

    /**
     * Page factory.
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $resultJsonFactory
     * @param \AutifyDigital\LloydscardnetPayment\Helper\Data $helper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        \AutifyDigital\LloydscardnetPayment\Helper\Data $helper
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        parent::__construct(
            $context,
            $productHelper,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory
        );
    }

    /**
     * Execute action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog('Admin Place Order Controller Called');
        $resultJson = $this->resultJsonFactory->create();

        try {
            // Use getParam instead of getPost to satisfy static analysis and provide a default empty array.
            $paymentData = (array)$this->getRequest()->getParam('payment', []);

            if (!empty($paymentData)) {
                // Ensure required checks are present.
                $paymentData['checks'] = [
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_INTERNAL,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
                ];

                // Use null coalescing and explicit casts so offsets always exist and types are stable.
                $paymentData['method_used'] = $paymentData['method_used'] ?? 'lcnetredirect';
                $paymentData['save_card'] = isset($paymentData['save_card']) ? (int)$paymentData['save_card'] : 0;

                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }

            // Process action data (save, etc.) - same behaviour as before.
            $this->_processActionData('save');

            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData((array)$this->getRequest()->getParam('order', []))
                ->createOrder();

            $this->_getSession()->clearStorage();

            // messageManager::addSuccessMessage expects a string in some static-analysis setups; cast Phrase to string.
            $this->messageManager->addSuccessMessage((string)__('You created the order.'));

            $order_id = null;
            if ($this->_authorization->isAllowed('Magento_Sales::actions_view')) {
                $order_id = ['order_id' => $order->getId()];
            }

            $this->helper->addLog('Order created successfully. Order ID: ' . $order->getId());

            return $resultJson->setData([
                'success' => true,
                'order_id' => $order_id,
                'error_messages' => ''
            ]);
        } catch (PaymentException $e) {
            $this->helper->addLog('Payment Exception: ' . $e->getMessage());
            $this->_getOrderCreateModel()->saveQuote();
            return $this->getErrorResponse((string)$e->getMessage());
        } catch (LocalizedException $e) {
            $this->helper->addLog('Localized Exception: ' . $e->getMessage());
            // Preserve customer id in session as before.
            $this->_getSession()->setCustomerId((int)$this->_getSession()->getQuote()->getCustomerId());
            return $this->getErrorResponse((string)$e->getMessage());
        } catch (\Exception $e) {
            $this->helper->addLog('General Exception: ' . $e->getMessage());
            // Cast Phrase to string so our getErrorResponse signature is satisfied.
            return $this->getErrorResponse((string)__('Order saving error: %1', $e->getMessage()));
        }
    }

    /**
     * Get error response as JSON result.
     *
     * @param string $msg
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getErrorResponse(string $msg)
    {
        if (trim($msg) === '') {
            $msg = (string)__('Your payment has been declined. Please try again.');
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

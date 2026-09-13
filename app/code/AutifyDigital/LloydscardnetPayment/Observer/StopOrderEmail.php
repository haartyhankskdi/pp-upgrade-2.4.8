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

namespace AutifyDigital\LloydscardnetPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use AutifyDigital\LloydscardnetPayment\Model\Config;
use Magento\Sales\Api\OrderRepositoryInterface;

class StopOrderEmail implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Config $config
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Config $config,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
    }
    /**
     * Execute Observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $method  = $payment->getMethodInstance()->getCode();

        if (in_array(
            $method,
            [
                'lcnetredirect',
                'lcnetpaymentjs',
                'cardnetapplepay',
                'cardnetgooglepay',
                'lbopcheckoutsolution'
            ],
            true
        )) {
            $this->stopNewOrderEmail($order, $method);
        }
    }

    /**
     * Set Stop Order Email Data
     *
     * @param Order  $order
     * @param string $payment
     */
    public function stopNewOrderEmail(Order $order, string $payment): void
    {
        $orderStatus = 'pending_payment';

        if ($payment === 'lcnetredirect') {
            $orderStatus = $this->config->getConfig('payment/lcnetredirect/order_status');
        } elseif ($payment === 'lcnetpaymentjs') {
            $orderStatus = $this->config->getConfig('payment/lcnetpaymentjs/order_status');
        } elseif ($payment === 'cardnetapplepay') {
            $orderStatus = $this->config->getConfig('payment/lcnetpaymentjs/order_status');
        } elseif ($payment === 'cardnetgooglepay') {
            $orderStatus = $this->config->getConfig('payment/lcnetpaymentjs/order_status');
        }

        if ($orderStatus === 'pending_payment') {
            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus('pending_payment');
        } else {
            $order->setState(Order::STATE_NEW)->setStatus('pending');
        }

        $order->setCanSendNewEmailFlag(false);
        $order->setSendEmail(false);
        $this->orderRepository->save($order);
    }
}

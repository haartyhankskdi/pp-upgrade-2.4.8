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

namespace AutifyDigital\LloydscardnetPayment\Controller\Wallet;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class Clear implements HttpPostActionInterface
{
    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Clear constructor.
     *
     * @param CheckoutSession   $checkoutSession
     * @param ManagerInterface  $messageManager
     * @param JsonFactory       $jsonFactory
     * @param UrlInterface      $url
     * @param RequestInterface  $request
     * @param Cart              $cart
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ManagerInterface $messageManager,
        JsonFactory $jsonFactory,
        UrlInterface $url,
        RequestInterface $request,
        Cart $cart
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager  = $messageManager;
        $this->jsonFactory     = $jsonFactory;
        $this->url             = $url;
        $this->request         = $request;
        $this->cart            = $cart;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $quote = $this->checkoutSession->getQuote();

            if ($quote->getId() && $quote->getItemsCount() > 0) {
                $itemIds = [];
                foreach ($quote->getAllItems() as $item) {
                    $itemIds[] = $item->getId();
                }

                foreach ($itemIds as $itemId) {
                    $this->cart->removeItem($itemId);
                }

                $this->cart->save();

                $this->checkoutSession->resetCheckout();
                $this->checkoutSession->clearQuote();
                $this->checkoutSession->clearStorage();

                return $result->setData(['status' => 'success']);
            }

            return $result->setData([
                'status'  => 'notice',
                'message' => __('Your cart is already empty.')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}

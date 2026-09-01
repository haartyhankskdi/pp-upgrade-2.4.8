<?php
namespace Kdi\CartLimit\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class CartAddBefore implements ObserverInterface {

    protected $messageManager;
    protected $checkoutSession;

    public function __construct(
        ManagerInterface $messageManager,
        CheckoutSession $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        // echo 'ncbkjvcxjvdjk';die;
        $quote = $this->checkoutSession->getQuote();
        $URL = $this->storeManager->getStore()->getUrl();
        $storeId = $this->storeManager->getStore()->getId();


            // Check if the quote already has items
            if ($quote->getItemsCount() > 0) {
                $this->messageManager->addError(__('Oops - you are only allowed to checkout one prescription item at a time.'));
                // Redirect to the cart page
                $controller = $observer->getControllerAction();
                $controller->getResponse()->setRedirect($URL . 'checkout/cart')->sendResponse();
                exit();
        }
    }
}


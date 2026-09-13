<?php
/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

namespace AutifyDigital\LloydscardnetPayment\Observer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Checkout\Block\QuoteShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AutifyDigital\LloydscardnetPayment\Block\Applepay\Minicart;

class AddApplePayShortcutButton implements ObserverInterface
{
 
    /**
     * Add an Apple Pay shortcut button to the minicart.
     *
     * This button is only added if the payment method is enabled and the observer is not in the catalog product.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // We only want to show this button in the minicart
        if ($observer->getData('is_catalog_product') || !$this->isEnabled()) {
            return;
        }

        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getData('container');
        
        $shortcut = $shortcutButtons->getLayout()->createBlock(Minicart::class);
        
        if (!$shortcut instanceof Minicart) {
            return;
        }
        
        $shortcut->setData('is_cart', get_class($shortcutButtons) === QuoteShortcutButtons::class);
        if (get_class($shortcutButtons) === QuoteShortcutButtons::class) {
            return;
        }
        $shortcutButtons->addShortcut($shortcut);
    }

    /**
     * Returns whether the Apple Pay shortcut button is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return true;
    }
}

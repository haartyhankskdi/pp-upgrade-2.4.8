<?php

/*
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-2026 Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as LicenseHelper;

class LicenseActivation implements ObserverInterface
{
    /**
     * @var string
     */
    private const LICENSE_KEY_PATH = 'payment/lloyds/license_key';

    /**
     * @var LicenseHelper
     */
    private $licenseHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param LicenseHelper $licenseHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LicenseHelper $licenseHelper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->licenseHelper = $licenseHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Activate license when payment settings are saved
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $websiteId = $observer->getEvent()->getWebsite();
            $store = $observer->getEvent()->getStore();

            if ($store) {
                $this->activateForStore($store);
            } elseif ($websiteId) {
                $this->activateForWebsite($websiteId);
            } else {
                $this->activateAllWebsites();
            }
        } catch (\Exception $e) {
            $this->licenseHelper->addLog('License activation on config save error: ' . $e->getMessage());
        }
    }

    /**
     * Activate license for a specific store's website
     *
     * @param string|int $storeId
     * @return void
     */
    private function activateForStore($storeId): void
    {
        $store = $this->storeManager->getStore($storeId);
        $this->activateForWebsite($store->getWebsiteId());
    }

    /**
     * Activate license for a specific website
     *
     * @param string|int $websiteId
     * @return void
     */
    private function activateForWebsite($websiteId): void
    {
        $licenseKey = $this->scopeConfig->getValue(
            self::LICENSE_KEY_PATH,
            ScopeInterface::SCOPE_WEBSITES,
            $websiteId
        );

        if (!empty($licenseKey)) {
            return;
        }

        $website = $this->storeManager->getWebsite($websiteId);
        $this->licenseHelper->addLog("Activating license for website: {$website->getCode()} (ID: {$websiteId})");

        $result = $this->licenseHelper->activate($websiteId);

        if ($result['success']) {
            $this->licenseHelper->addLog("License activated for website: {$website->getCode()} " .
                'license_key: ' . ($result['license_key'] ?? 'N/A')
            );
        } else {
            $this->licenseHelper->addLog("License activation failed for website: {$website->getCode()} " .
                'message: ' . ($result['message'] ?? 'Unknown error')
            );
        }
    }

    /**
     * Activate license for all websites that don't have one
     *
     * @return void
     */
    private function activateAllWebsites(): void
    {
        $websites = $this->storeManager->getWebsites(false);

        foreach ($websites as $website) {
            $this->activateForWebsite($website->getId());
        }
    }
}

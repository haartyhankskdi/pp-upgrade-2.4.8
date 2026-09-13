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

namespace AutifyDigital\LloydscardnetPayment\Cron;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use AutifyDigital\LloydscardnetPayment\Helper\Data as LicenseHelper;

/**
 * Cron job to check and activate license
 */
class LicenseCheck
{
    /**
     * @var string
     */
    public const MODULE_NAME = 'AutifyDigital_LloydscardnetPayment';

    /**
     * @var string
     */
    public const LICENSE_KEY_PATH = 'payment/lloyds/license_key';

    /**
     * @var LicenseHelper
     */
    protected $licenseHelper;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * LicenseCheck constructor
     *
     * @param LicenseHelper $licenseHelper
     * @param ModuleManager $moduleManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LicenseHelper $licenseHelper,
        ModuleManager $moduleManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->licenseHelper = $licenseHelper;
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->licenseHelper->addLog('AutifyDigital License Cron: Starting license check');

            $isModuleEnabled = $this->moduleManager->isEnabled(self::MODULE_NAME);

            if (!$isModuleEnabled) {
                $this->licenseHelper->addLog('AutifyDigital License Cron: Module is disabled, skipping');
                return;
            }

            $licenseKey = $this->scopeConfig->getValue(
                self::LICENSE_KEY_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $hasLicenseKey = !empty($licenseKey);

            if (!$hasLicenseKey) {
                $this->licenseHelper->addLog('AutifyDigital License Cron: No license key found. Attempting activation...');
                
                $result = $this->licenseHelper->activate();

                if ($result['success']) {
                    $this->licenseHelper->addLog(
                        'AutifyDigital License Cron: Activation successful, license_key: '
                        . ($result['license_key'] ?? 'N/A')
                    );
                } else {
                    $this->licenseHelper->addLog(
                        'AutifyDigital License Cron: Activation failed, message: '
                        . ($result['message'] ?? 'Unknown error')
                    );
                }
            } else {
                $this->licenseHelper->addLog('AutifyDigital License Cron: License key exists, no action needed');
            }

        } catch (\Exception $e) {
            $this->licenseHelper->addLog('AutifyDigital License Cron Error: ' . $e->getMessage());
        }
    }
}

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

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;

class EncryptedStoreId extends Encrypted
{
    /**
     * Validate store_id before saving
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();
        
        // Check if value is masked (unchanged from previous save)
        if ($this->isMasked($value)) {
            // Don't validate masked values - they haven't changed
            parent::beforeSave();
            return $this;
        }

        // Only validate if there's actually a value
        if ($value !== '' && $value !== null) {
            // Check if it starts with "22"
            if (substr($value, 0, 2) !== '22') {
                throw new LocalizedException(
                    __('Store ID must start with "22"')
                );
            }

            if (!is_numeric($value)) {
                throw new LocalizedException(
                    __('Store ID must be numeric')
                );
            }
        }

        parent::beforeSave();
        return $this;
    }

    /**
     * Detect if Magento masked the value with asterisks
     *
     * @param string $value
     * @return bool
     */
    private function isMasked(string $value): bool
    {
        return preg_match('/^\*+$/', $value) === 1;
    }
}

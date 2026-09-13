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

namespace AutifyDigital\LloydscardnetPayment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentMethods implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
         return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => 'V', 'label' => __('Visa')],
            ['value' => 'M', 'label' => __('Mastercard')],
            ['value' => 'A', 'label' => __('American Express')],
            ['value' => 'MA', 'label' => __('Maestro')],
            ['value' => 'googlePay', 'label' => __('Google Pay')],
            ['value' => 'applePay', 'label' => __('Apple Pay')],
            ['value' => 'paypal', 'label' => __('PayPal')],
         ];
    }
}

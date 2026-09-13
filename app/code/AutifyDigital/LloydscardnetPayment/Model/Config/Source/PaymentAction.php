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

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    public const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';
    public const ACTION_AUTHORIZE = 'authorize';

    /**
     * Return Array of Modes
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ],
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => __('Authorize Only')
            ]
        ];
    }
}

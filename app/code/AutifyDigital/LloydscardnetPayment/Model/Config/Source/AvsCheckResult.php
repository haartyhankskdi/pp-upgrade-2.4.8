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

/**
 * Options for the AVS / Postcode / CVV check result grid columns.
 */
class AvsCheckResult implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'Y', 'label' => __('Pass')],
            ['value' => 'N', 'label' => __('Fail')],
            ['value' => 'unchecked', 'label' => __('Not Authenticated')],
        ];
    }
}

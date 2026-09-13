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

class ChallengeIndicator implements ArrayInterface
{
    /**
     * Return Array of Modes
     *
     * @return array array in configuration
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '01',
                'label' => '01 - No preference (You have no preference whether a challenge should be performed.)',
            ],
            [
                'value' => '02',
                'label' => '02 - No challenge requested (You prefer that no challenge should be performed.)',
            ],
            [
                'value' => '03',
                'label' => '03 - Challenge requested: 3DS Requestor Preference (You prefer that a challenge should be performed)',
            ],
            [
                'value' => '04',
                'label' => '04 - Challenge requested: Mandate (There are local or regional mandates that mean that a challenge must be performed)',
            ],
        ];
    }
}

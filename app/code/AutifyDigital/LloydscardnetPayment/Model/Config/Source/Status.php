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
 * Class ApplicationStatus
 */
class Status implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => 'Please Select', 'value' => ''];
        $options[] = ['label' => 'Pending', 'value' => '1'];
        $options[] = ['label' => 'Paid', 'value' => '2'];
        $options[] = ['label' => 'Cancel', 'value' => '3'];
        $options[] = ['label' => 'Error', 'value' => '4'];
        $options[] = ['label' => 'Refunded', 'value' => '5'];
        return $options;
    }
}

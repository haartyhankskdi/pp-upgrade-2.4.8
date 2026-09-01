<?php
/**
 * Copyright © Magenest, Inc. All rights reserved.
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TransType implements OptionSourceInterface
{
    /**
     * Trans type options
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'GoodsAndServicePurchase',
                'label' => __('Goods/ Service Purchase'),
            ],
            [
                'value' => 'CheckAcceptance',
                'label' => __('Check Acceptance')
            ],
            [
                'value' => 'AccountFunding',
                'label' => __('Account Funding')
            ],
            [
                'value' => 'QuasiCashTransaction',
                'label' => __('Quasi-Cash Transaction')
            ],
            [
                'value' => 'PrepaidActivationAndLoad',
                'label' => __('Prepaid Activation and Load')
            ],
        ];
    }
}

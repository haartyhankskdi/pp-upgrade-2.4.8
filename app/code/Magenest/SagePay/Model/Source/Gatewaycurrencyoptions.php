<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magenest\SagePay\Model\Source;

class Gatewaycurrencyoptions implements \Magento\Framework\Option\ArrayInterface
{
    const BASE  = 'base';
    const FRONT = 'front';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::BASE,
                'label' => __('Base Currency')
            ],
            [
                'value' => self::FRONT,
                'label' => __('Front Currency')
            ]
        ];
    }
}

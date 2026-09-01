<?php

namespace Amasty\Reports\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public const PROCESSING = 0;
    public const COMPLETE = 1;
    public const EMPTY_CART = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PROCESSING,
                'label' => __('Processing')
            ],
            [
                'value' => self::COMPLETE,
                'label' => __('Complete')
            ],
            [
                'value' => self::EMPTY_CART,
                'label' => __('Empty cart')
            ]
        ];
    }
}

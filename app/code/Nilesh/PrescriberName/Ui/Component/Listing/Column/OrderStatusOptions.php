<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Nilesh\PrescriberName\Ui\Component\Listing\Column;

/**
 * Class Options
 */
class OrderStatusOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __(''),
            ],
            [
                'value' => 1,
                'label' => __('Do Not Supply'),
            ],
            [
                'value' => 2,
                'label' => __('Repeat Order'),
            ],
            [
                'value' => 3,
                'label' => __('Do Not Supply & Repeat Order'),
            ]
        ];
    }
}

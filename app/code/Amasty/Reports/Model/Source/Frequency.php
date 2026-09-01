<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Source;

class Frequency implements \Magento\Framework\Data\OptionSourceInterface
{
    public const CUSTOM = 1;

    public const EVERY_DAY_9AM = 2;

    public const EVERY_MONDAY_9AM = 3;

    public const EVERY_FIRST_DAY_MONTH_9AM = 4;

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::CUSTOM,
                'label' => __('Custom')
            ],
            [
                'value' => self::EVERY_DAY_9AM,
                'label' => __('Every Day at 9am')
            ],
            [
                'value' => self::EVERY_MONDAY_9AM,
                'label' => __('Every Monday at 9am')
            ],
            [
                'value' => self::EVERY_FIRST_DAY_MONTH_9AM,
                'label' => __('Every 1st Day of Month at 9am')
            ]
        ];
    }
}

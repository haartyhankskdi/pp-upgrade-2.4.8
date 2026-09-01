<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Source\Date;

use Magento\Framework\Phrase;

class Interval implements \Magento\Framework\Data\OptionSourceInterface
{
    public const DAY = 1;

    public const MONTH = 2;

    public const YEAR = 3;

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::DAY,
                'label' => __('Day(s)')
            ],
            [
                'value' => self::MONTH,
                'label' => __('Month(s)')
            ],
            [
                'value' => self::YEAR,
                'label' => __('Year(s)')
            ]
        ];
    }

    public function getLabelByValue(int $value): string
    {
        $label = '';
        foreach ($this->toOptionArray() as $source) {
            if ($source['value'] == $value) {
                $label = $source['label']->render();
                break;
            }
        }

        return  $label;
    }
}

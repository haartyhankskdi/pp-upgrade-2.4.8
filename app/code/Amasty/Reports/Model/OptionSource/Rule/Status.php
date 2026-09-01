<?php

namespace Amasty\Reports\Model\OptionSource\Rule;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public const INDEXED = 1;
    public const PROCESSING = 0;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::INDEXED,
                'label' => __('Indexed')
            ],
            [
                'value' => self::PROCESSING,
                'label' => __('Processing')
            ]
        ];
    }
}

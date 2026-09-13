<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public const ANSWERED = 1;
    public const PENDING = 0;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::PENDING, 'label' => __('Pending')],
            ['value' => self::ANSWERED, 'label' => __('Answered')],
        ];
    }
}

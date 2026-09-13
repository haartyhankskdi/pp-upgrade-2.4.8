<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Source;

use Amasty\Customform\Model\Form;
use Magento\Framework\Data\OptionSourceInterface;

class FormStatus implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Enabled'),
                'value' => Form::STATUS_ENABLED
            ],
            [
                'label' => __('Disabled'),
                'value' => Form::STATUS_DISABLED
            ]
        ];
    }
}

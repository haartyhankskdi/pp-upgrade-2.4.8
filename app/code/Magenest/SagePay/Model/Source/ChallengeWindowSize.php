<?php
/**
 * Copyright © Magenest, Inc. All rights reserved.
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ChallengeWindowSize implements OptionSourceInterface
{
    /**
     * Challenger window size options
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Small',
                'label' => __('250 x 400'),
            ],
            [
                'value' => 'Medium',
                'label' => __('390 x 400')
            ],
            [
                'value' => 'Large',
                'label' => __('500 x 600')
            ],
            [
                'value' => 'ExtraLarge',
                'label' => __('600 x 400')
            ],
            [
                'value' => 'FullScreen',
                'label' => __('Full screen')
            ],
        ];
    }
}

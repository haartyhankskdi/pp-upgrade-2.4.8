<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

class Language implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => "",
                'label' => __('Default'),
            ],
            [
                'value' => 'English',
                'label' => __('English'),
            ],
            [
                'value' => 'French',
                'label' => __('French'),
            ],
            [
                'value' => 'German',
                'label' => __('German'),
            ],
            [
                'value' => 'Spanish',
                'label' => __('Spanish'),
            ],
            [
                'value' => 'Portuguese',
                'label' => __('Portuguese'),
            ],
            [
                'value' => 'Dutch',
                'label' => __('Dutch'),
            ]
        ];
    }
}

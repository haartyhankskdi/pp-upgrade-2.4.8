<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Dropin implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'modal',
                'label' => __('Modal UI')
            ],
            [
                'value' => 'inline',
                'label' => __('Inline UI'),
            ],
        ];
    }
}

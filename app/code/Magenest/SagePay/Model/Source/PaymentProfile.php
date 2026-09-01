<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentProfile implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'NORMAL',
                'label' => __('Normal Profile Mode')
            ],
            [
                'value' => 'LOW',
                'label' => __('Low Profile Mode'),
            ],
        ];
    }
}

<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

class SendEmail implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('Do not send either customer or vendor Email'),
            ],
            [
                'value' => '1',
                'label' => __('Send customer and vendor transaction Email'),
            ],
            [
                'value' => '2',
                'label' => __('Send Vendor Email but not Customer Email'),
            ]
        ];
    }
}

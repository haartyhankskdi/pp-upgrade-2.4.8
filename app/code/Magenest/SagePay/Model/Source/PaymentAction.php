<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    const AUTHORIZE = 'authorize';

    const AUTHORIZE_AND_CAPTURE = 'authorize_capture';

    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture (Payment)')
            ],
            [
                'value' => 'authorize',
                'label' => __('Authorize Only (Deferred)'),
            ],
        ];
    }
}

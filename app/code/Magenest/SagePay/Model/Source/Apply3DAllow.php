<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

class Apply3DAllow implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Authenticated',
                'label' => __('Authenticated'),
            ],
            [
                'value' => 'NotChecked',
                'label' => __('NotChecked'),
            ],
            [
                'value' => 'NotAuthenticated',
                'label' => __('NotAuthenticated'),
            ],
            [
                'value' => 'Error',
                'label' => __('Error'),
            ],
            [
                'value' => 'CardNotEnrolled',
                'label' => __('CardNotEnrolled'),
            ],[
                'value' => 'IssuerNotEnrolled',
                'label' => __('IssuerNotEnrolled'),
            ],
            [
                'value' => 'MalformedOrInvalid',
                'label' => __('MalformedOrInvalid'),
            ],
            [
                'value' => 'AttemptOnly',
                'label' => __('AttemptOnly'),
            ],
            [
                'value' => 'Incomplete',
                'label' => __('Incomplete'),
            ],
        ];
    }
}

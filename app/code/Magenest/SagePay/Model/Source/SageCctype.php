<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Payment\Model\Source\Cctype as PaymentCctype;

class SageCctype extends PaymentCctype
{
    public function getAllSageCardType()
    {
        return [
            'VISA' => __('VISA Credit'),
            'DELTA' => __('VISA Debit'),
            'UKE' => __('VISA Electron'),
            'MC' => __('MasterCard'),
            'MCDEBIT' => __('Debit MasterCard'),
            'MAESTRO' => __('Maestro'),
            'AMEX' => __('American Express'),
            'DC' => __('Diner\'s Club'),
            'JCB' => __('JCB Card'),
            'LASER' => __('Laser'),
        ];
    }

    public function toOptionArray()
    {
        $options = [];
        $sageCardType = $this->getAllSageCardType();
        foreach ($sageCardType as $value => $label) {
            $options [] =
                [
                    'value' => $value,
                    'label' => $label
                ];
        }
        return $options;
    }
}

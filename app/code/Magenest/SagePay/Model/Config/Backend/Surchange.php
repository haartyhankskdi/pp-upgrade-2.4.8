<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Config\Backend;

class Surchange extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{

    public function validateBeforeSave()
    {
        $value = $this->getValue();
        foreach ($value as $record) {
            if (is_array($record)) {
                $this->validateSurchangeRecord($record);
            }
        }
        parent::validateBeforeSave();
    }

    public function validateSurchangeRecord($surchangeRecord)
    {
        $value = isset($surchangeRecord['value']) ? $surchangeRecord['value'] : '';
        if (!is_numeric($value)) {
            throw new \Magento\Framework\Validator\Exception(__("Invalid Surchange Value"));
        }
        $type = isset($surchangeRecord['surchange_type']) ? $surchangeRecord['surchange_type'] : null;
        if ($type == 'percentage' && $value >=100) {
            throw new \Magento\Framework\Validator\Exception(__("Invalid Surchange Value"));
        }
    }
}

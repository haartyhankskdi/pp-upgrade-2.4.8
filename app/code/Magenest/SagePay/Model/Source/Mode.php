<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['value' => 1, 'label' => __('Test Mode')], ['value' => 0, 'label' => __('Live Mode')]];
    }

    public function toArray()
    {
        return [0 => __('Live Mode'), 1 => __('Test Mode')];
    }
}

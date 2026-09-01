<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class IsSubscription extends AbstractSource
{
    public function getOptionArray()
    {
        return [
            1 => __('Yes'),
            0 => __('No')
        ];
    }

    public function getAllOptions()
    {
        $result = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    public function getOptionGrid($optionId)
    {
        $options = $this->getOptionArray();
        if ($optionId == 1) {
            $html = '<span class="grid-severity-notice"><span>' . $options[$optionId] . '</span>' . '</span>';
        } else {
            $html = '<span class="grid-severity-critical"><span>' . $options[$optionId] . '</span></span>';
        }

        return $html;
    }
}

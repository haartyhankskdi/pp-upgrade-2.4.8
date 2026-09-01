<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config\Field;

class SurchangeType extends \Magento\Framework\View\Element\Html\Select
{

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function getSurchangeType()
    {
        return [
            [
                'value' => 'fixed',
                'label' => 'Fix'
            ],
            [
                'value' => 'percentage',
                'label' => 'Percentage'
            ]
        ];
    }

    public function toHtml()
    {
        $surchangeTypes = $this->getSurchangeType();
        if (!$this->getOptions()) {
            foreach ($surchangeTypes as $surchangeType) {
                $this->addOption($surchangeType['value'], $surchangeType['label']);
            }
        }
        return parent::toHtml();
    }
}

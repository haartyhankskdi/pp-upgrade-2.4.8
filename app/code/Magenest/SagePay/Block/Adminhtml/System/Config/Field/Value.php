<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config\Field;

class Value extends \Magento\Framework\View\Element\AbstractBlock
{

    protected $name;

    public function toHtml()
    {
        return '<input type="number" name="'.$this->getName().'">';
    }

    public function getName()
    {
        return $this->name;
    }
}

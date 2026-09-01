<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Sales\Coupon;

use Magento\Framework\Data\Form\AbstractForm;

class Toolbar extends \Amasty\Reports\Block\Adminhtml\Report\Toolbar
{
    /**
     * @param AbstractForm $form
     *
     * @return $this
     */
    protected function addControls(AbstractForm $form)
    {
        $this->addDateControls($form);

        $form->addField('value', 'radios', [
            'name'      => 'value',
            'wrapper_class' => 'amreports-filter-interval amreports-filter-switcher',
            'values'    => [
                ['value' => 'total', 'label' => __('Total')],
                ['value' => 'items', 'label' => __('Items Ordered')]
            ],
            'value'     => 'total'
        ]);
        
        return parent::addControls($form);
    }
}

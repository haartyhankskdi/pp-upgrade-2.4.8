<?php

declare(strict_types=1);

namespace Amasty\Reports\Block\Adminhtml\Report\Sales\Quote;

use Magento\Framework\Data\Form\AbstractForm;

class Toolbar extends \Amasty\Reports\Block\Adminhtml\Report\Toolbar
{
    protected function addControls(AbstractForm $form)
    {
        $this->addDateControls($form);
        $form->addField('value', 'radios', [
            'name'      => 'value',
            'wrapper_class' => 'amreports-filter-interval amreports-filter-switcher',
            'values'    => [
                ['value' => 'quantity', 'label' => __('Quantity')],
                ['value' => 'total', 'label' => __('Total')]
            ],
            'value'     => 'total'
        ]);

        return parent::addControls($form);
    }
}

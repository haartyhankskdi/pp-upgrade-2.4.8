<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Customers\Abandoned;

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

        $form->addField('interval', 'radios', [
            'name' => 'interval',
            'wrapper_class' => 'amreports-filter-interval',
            'values'    => [
                ['value' => 'day', 'label' => __('Day')],
                ['value' => 'week', 'label' => __('Week')],
                ['value' => 'month', 'label' => __('Month')],
                ['value' => 'year', 'label' => __('Year')],
            ],
            'value' => 'day'
        ]);

        $this->addViewControls(
            $form,
            [
                ['value' => 'line', 'label' => __('Line')],
                ['value' => 'column', 'label' => __('Columns')]
            ],
            'column'
        );

        parent::addControls($form);

        return $this;
    }
}

<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Sales\OrderItems;

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

        return parent::addControls($form);
    }
}

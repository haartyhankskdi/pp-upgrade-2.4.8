<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Catalog\ByProduct;

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
        $this->addRuleControl($form);

        return parent::addControls($form);
    }
}

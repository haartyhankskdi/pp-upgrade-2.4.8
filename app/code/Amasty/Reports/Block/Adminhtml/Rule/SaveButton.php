<?php

namespace Amasty\Reports\Block\Adminhtml\Rule;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [
            'label'    => __('Save'),
            'class'    => 'save primary',
            'on_click' => '',
        ];
    }
}

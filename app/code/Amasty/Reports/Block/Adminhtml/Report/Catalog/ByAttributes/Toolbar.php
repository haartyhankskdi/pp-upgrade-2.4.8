<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Catalog\ByAttributes;

use Magento\Framework\Data\Form\AbstractForm;

class Toolbar extends \Amasty\Reports\Block\Adminhtml\Report\Toolbar
{
    public const ATTRIBUTE_SET = 'attrset';

    /**
     * @param AbstractForm $form
     *
     * @return $this
     */
    protected function addControls(AbstractForm $form)
    {
        $this->addDateControls($form);
        $this->addAttributes($form);

        return parent::addControls($form);
    }

    /**
     * @param $form
     */
    protected function addAttributes($form)
    {
        $attributesOptions = $this->indexedAttributes->getAttributesOptions();
        $outputAttributes = [
            ['label' => __('Attributes')->render(), 'value' => $attributesOptions],
            [
                'label' => __('Other')->render(),
                'value' => [['label' => __('Attribute Set')->render(), 'value' => self::ATTRIBUTE_SET]]
            ]
        ];

        $form->addField('eav', 'select', [
            'name' => 'eav',
            'values' => $outputAttributes,
            'wrapper_class' => 'amreports-select-block',
            'class' => 'amreports-select',
            'no_span' => true
        ]);

        $form->addField('value', 'radios', [
            'name' => 'value',
            'wrapper_class' => 'amreports-filter-interval amreports-filter-switcher',
            'values' => [
                ['value' => 'quantity', 'label' => __('Quantity')],
                ['value' => 'total', 'label' => __('Total')]
            ],
            'value' => 'quantity'
        ]);
    }
}

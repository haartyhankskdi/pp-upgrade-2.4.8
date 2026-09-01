<?php

namespace Amasty\Reports\Block\Adminhtml\Report\Sales\Overview\Compare;

use Magento\Framework\Data\Form\AbstractForm;

class Toolbar extends \Amasty\Reports\Block\Adminhtml\Report\Sales\Overview\Toolbar
{
    public const FIELDSET_COUNT = 3;
    public const COLOR_FIRST_LINE = '#78b5d9';
    public const COLOR_SECOND_LINE = '#6f94d7';
    public const COLOR_THIRD_LINE = '#7c69d6';

    /**
     * @param AbstractForm $parentElement
     * @return $this|\Amasty\Reports\Block\Adminhtml\Report\Sales\Overview\Toolbar
     */
    protected function addDateControls(AbstractForm $parentElement)
    {
        $dateFormat = 'y-MM-dd';
        for ($i = 0; $i < self::FIELDSET_COUNT; $i++) {
            $fieldset = $parentElement->addFieldset(
                'from_to_' . $i,
                [
                    'legend' => __('Range #%1   ', $i + 1),
                    'class' => 'amreports-ranges-fieldset',
                    'wrapperclass' => 'amreports-fieldset-container'
                ]
            );

            list($fromValue, $toValue) = $this->getFromToValues($i);

            $fieldset->addField(
                'from_' . $i,
                'date',
                [
                    'label' => __('From'),
                    'name' => 'from_' . $i,
                    'date_format' => $dateFormat,
                    'format' => $dateFormat,
                    'value' => $fromValue
                ]
            );

            $fieldset->addField(
                'to_' . $i,
                'date',
                [
                    'label' => __('To'),
                    'name' => 'to_' . $i,
                    'format' => $dateFormat,
                    'date_format' => $dateFormat,
                    'value' => $toValue
                ]
            );
        }

        return $this;
    }

    /**
     * @param $index
     * @return array
     */
    private function getFromToValues($index)
    {
        $fromValue = null;
        $toValue = null;
        if ($index == 0) {
            $fromValue = $this->_localeDate->date($this->helper->getDefaultFromDate());
            $toValue = $this->_localeDate->date($this->helper->getDefaultToDate());
        }

        return [$fromValue, $toValue];
    }

    /**
     * @param AbstractForm $form
     * @return $this|\Amasty\Reports\Block\Adminhtml\Report\Sales\Overview\Toolbar
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addControls(AbstractForm $form)
    {
        parent::addControls($form);
        $form->addField(
            'submit',
            'note',
            [
                'text' => $this->getLayout()->createBlock(
                    \Magento\Backend\Block\Widget\Button::class
                )->setData(
                    ['label' => __('Compare'), 'class' => 'left']
                )->toHtml()
            ]
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getDataRole()
    {
        return 'amreports-toolbar_on_button';
    }

    /**
     * @inheritdoc
     */
    protected function addViewControls(AbstractForm $form, $values, $defaultValue)
    {
        return $this;
    }
}

<?php
namespace Magezon\PopupBuilder\Data\Element;

class PopupAction extends \Magezon\Builder\Data\Element\Button
{
    /**
     * @return \Magezon\Builder\Data\Form\Element\Fieldset
     */
    public function prepareGeneralTab()
    {
        $general = parent::prepareGeneralTab();

        $general->addChildren(
            'action_type',
            'select',
            [
                'sortOrder'       => 5,
                'key'             => 'action_type',
                'defaultValue'    => 'link',
                'templateOptions' => [
                    'label'   => __('Action Type'),
                    'options' => $this->getActionType()
                ]
            ]
        );

        $general->removeElement('link');
        $general->removeElement('onclick_code');

        return $general;
    }

    /**
     * @return array
     */
    public function getActionType()
    {
        return [
            [
                'label' => __('Link'),
                'value' => 'link'
            ],
            [
                'label' => __('Leave Page'),
                'value' => 'leave'
            ],
            [
                'label' => __('Close Popup'),
                'value' => 'close-popup'
            ],
            [
                'label' => __('Close Popup Сonstantly'),
                'value' => 'close-constantly'
            ],
            [
                'label' => __('Close All Popups Сonstantly'),
                'value' => 'close-all-constantly'
            ]
        ];
    }
}

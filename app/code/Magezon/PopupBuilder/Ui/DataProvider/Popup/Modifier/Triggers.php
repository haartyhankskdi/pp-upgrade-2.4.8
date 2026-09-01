<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Ui\DataProvider\Popup\Modifier;

use Magento\Ui\Component\Form\Fieldset;

class Triggers extends AbstractModifier
{
    /**
     * @var array
     */
    protected $meta;

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->prepareChildren();

        $this->createPanel();

        return $this->meta;
    }

    /**
     * @return \Magezon\UiBuilder\Data\Form\Element\Fieldset
     */
    public function prepareChildren()
    {
        $container1 = $this->addContainerGroup(
            'container1',
            [
                'label'             => __('On Page Load'),
                'sortOrder'         => 10,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'
            ]
        );

        $container1->addChildren(
            'page_load',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                //'value'        => 1,
                'tooltip'      => [
                    'description' => __('If Yes, set the number of seconds to wait, upon page load, before the popup is triggered.')
                ]
            ]
        );

        $container1->addChildren(
            'page_load_delay',
            'number',
            [
                'label'     => __('Within (sec)'),
                'sortOrder' => 20,
                'required'  => true,
                'value'     => 0,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.page_load',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container2 = $this->addContainerGroup(
            'container2',
            [
                'label'             => __('On Scroll'),
                'sortOrder'         => 20,
                'additionalClasses' => 'mgz-popupbuilder-field-inline mgz-popupbuilder-field-margin'

            ]
        );

        $container2->addChildren(
            'scrolling',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, select Direction (Up or Down) and set the amount to scroll (%) before the popup is triggered.')
                ]
            ]
        );

        $container2->addChildren(
            'scrolling_direction',
            'select',
            [
                'label'     => __('Direction'),
                'sortOrder' => 20,
                'required'  => true,
                'options'   => $this->getDirectionOptions(),
                'value'     => 'down',
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.scrolling',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container2->addChildren(
            'scrolling_offset',
            'text',
            [
                'label'     => __('Within (%)'),
                'sortOrder' => 30,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.scrolling',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container3 = $this->addContainerGroup(
            'container3',
            [
                'label'             => __('On Scroll To Element'),
                'sortOrder'         => 30,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'
            ]
        );

        $container3->addChildren(
            'scrolling_to',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, enter the Selector name (element ID or class) that will trigger the popup when users scroll to it.')
                ]
            ]
        );

        $container3->addChildren(
            'scrolling_to_selector',
            'text',
            [
                'label'     => __('Selector'),
                'sortOrder' => 20,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.scrolling_to',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container4 = $this->addContainerGroup(
            'container4',
            [
                'label'             => __('On Hover'),
                'sortOrder'         => 40,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'
            ]
        );

        $container4->addChildren(
            'hover_on',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, enter the Selector name (element ID or class) that will trigger the popup when the user hovers over it.')
                ]
            ]
        );

        $container4->addChildren(
            'hover_selector',
            'text',
            [
                'label'     => __('Selector'),
                'sortOrder' => 20,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.hover_on',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container5 = $this->addContainerGroup(
            'container5',
            [
                'label'             => __('On Click'),
                'sortOrder'         => 50,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'
            ]
        );

        $container5->addChildren(
            'click',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, enter the number of clicks (on anywhere on the page) that will trigger the popup.')
                ]
            ]
        );

        $container5->addChildren(
            'click_times',
            'number',
            [
                'label'     => __('Clicks'),
                'sortOrder' => 20,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.click',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container6 = $this->addContainerGroup(
            'container6',
            [
                'label'             => __('After Inactivity'),
                'sortOrder'         => 60,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container6->addChildren(
            'inactivity',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, specify the number of seconds of user inactivity (mouse cursor inactivity) that will trigger the popup.')
                ]
            ]
        );

        $container6->addChildren(
            'inactivity_time',
            'number',
            [
                'label'     => __('Within (sec)'),
                'sortOrder' => 20,
                'required'  => true,
                'value'     => 30,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.inactivity',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container7 = $this->addContainerGroup(
            'container7',
            [
                'label'             => __('On Page Exit Intent'),
                'sortOrder'         => 70,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'
            ]
        );

        $container7->addChildren(
            'exit_intent',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, the popup will appear when the user’s mouse activity indicates intent to exit the page (when the cursor moves outside the upper page boundary).')
                ]
            ]
        );
    }

    /**
     * @return $this
     */
    protected function createPanel()
    {
        $children = $this->getChildren();
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                'conditions' => [
                    'children' => [
                        'triggers' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Triggers'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'opened'                          => false,
                                        'sortOrder'                       => 20,
                                        'dataScope'                       => 'data.conditions',
                                        'additionalClasses'               => 'mgz-popupbuilder-triggers'
                                    ]
                                ]
                            ],
                            'children' => $children
                        ]
                    ]
                ]
            ]
        );
        return $this;
    }

    /**
     * @return array
     */
    public function getDirectionOptions()
    {
        return [
            [
                'label' => __('Down'),
                'value' => 'down'
            ],
            [
                'label' => __('Up'),
                'value' => 'up'
            ]
        ];
    }
}

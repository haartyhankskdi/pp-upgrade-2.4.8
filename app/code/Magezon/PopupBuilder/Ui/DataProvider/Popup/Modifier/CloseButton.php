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

class CloseButton extends AbstractModifier
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
     * @return $this
     */
    protected function createPanel()
    {
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                'style' => [
                    'children' => [
                        'closebutton' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Close Button'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'sortOrder'                       => 30,
                                        'dataScope'                       => 'data.style_settings.closebutton'
                                    ]
                                ]
                            ],
                            'children' => $this->getChildren()
                        ]
                    ]
                ]
            ]
        );
        return $this;
    }

    /**
     * @return \Magezon\UiBuilder\Data\Form\Element\Fieldset
     */
    public function prepareChildren()
    {
        $this->addChildren(
            'position',
            'select',
            [
                'label'     => __('Position'),
                'sortOrder' => 10,
                'options'   => $this->getPositionOptions(),
                'value'     => 'inside'
            ]
        );

        $this->addChildren(
            'vertical_position',
            'number',
            [
                'label'     => __('Vertical Position'),
                'sortOrder' => 20
            ]
        );

        $this->addChildren(
            'horizontal_position',
            'number',
            [
                'label'     => __('Horizontal Position'),
                'sortOrder' => 30
            ]
        );

        $this->addChildren(
            'color',
            'color',
            [
                'label'     => __('Color'),
                'sortOrder' => 40,
                'rgb'       => true
            ]
        );

        $this->addChildren(
            'hover_color',
            'color',
            [
                'label'     => __('Hover Color'),
                'sortOrder' => 50,
                'rgb'       => true
            ]
        );

        $this->addChildren(
            'background_color',
            'color',
            [
                'label'     => __('Background Color'),
                'sortOrder' => 60,
                'rgb'       => true
            ]
        );

        $this->addChildren(
            'hover_background_color',
            'color',
            [
                'label'     => __('Hover Background Color'),
                'sortOrder' => 70,
                'rgb'       => true
            ]
        );

        $this->addChildren(
            'icon_size',
            'number',
            [
                'label'     => __('Icon Size'),
                'sortOrder' => 80
            ]
        );

        $this->addChildren(
            'border_radius',
            'number',
            [
                'label'     => __('Border Radius'),
                'sortOrder' => 90
            ]
        );

        $this->addChildren(
            'box_size',
            'number',
            [
                'label'     => __('Box Size'),
                'sortOrder' => 100
            ]
        );

        $container1 = $this->addContainerGroup(
            'container1',
            [
                'label'             => __('Box Shadow'),
                'sortOrder'         => 110,
                'additionalClasses' => 'popupbuilder-styling-boxshadow'
            ]
        );

            $container1->addChildren(
                'boxshadow',
                'boolean',
                [
                    'sortOrder' => 10,
                    'required'  => true
                ]
            );

            $container1->addChildren(
                'boxshadow_color',
                'color',
                [
                    'sortOrder' => 20,
                    'required'  => true,
                    'label'     => __('Color'),
                    'value'     => 'rgba(0, 0, 0, 0.2)',
                    'rgb'       => true,
                    'imports'   => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );

            $container1->addChildren(
                'boxshadow_horizontal',
                'number',
                [
                    'sortOrder' => 30,
                    'required'  => true,
                    'label'     => __('Horizontal'),
                    'value'     => 1,
                    'imports'   => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );

            $container1->addChildren(
                'boxshadow_vertical',
                'number',
                [
                    'sortOrder' => 40,
                    'required'  => true,
                    'value'     => 8,
                    'label'     => __('Vertical'),
                    'imports'   => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );

            $container1->addChildren(
                'boxshadow_blur',
                'number',
                [
                    'sortOrder' => 50,
                    'required'  => true,
                    'value'     => 23,
                    'label'     => __('Blur'),
                    'imports'   => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );

            $container1->addChildren(
                'boxshadow_spread',
                'number',
                [
                    'sortOrder'    => 60,
                    'required'     => true,
                    'value'        => 4,
                    'label'        => __('Spread'),
                    'imports'      => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );

            $container1->addChildren(
                'boxshadow_position',
                'select',
                [
                    'sortOrder' => 70,
                    'required'  => true,
                    'label'     => __('Position'),
                    'value'     => 'outline',
                    'options'   => $this->getBoxshadowPositionOptions(),
                    'imports'   => [
                        'visible' => '${ $.provider }:${ $.parentScope }.boxshadow',
                        '__disableTmpl' => ['visible' => false]
                    ]
                ]
            );
    }

    /**
     * @return array
     */
    public function getPositionOptions()
    {
        return [
            [
                'label' => __('Inside'),
                'value' => 'inside'
            ],
            [
                'label' => __('Outside'),
                'value' => 'outside'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getBackgroundSizeOptions()
    {
        return [
            [
                'label' => __('Auto'),
                'value' => 'auto'
            ],
            [
                'label' => __('Cover'),
                'value' => 'cover'
            ],
            [
                'label' => __('Contain'),
                'value' => 'contain'
            ],
            [
                'label' => __('Custom'),
                'value' => 'custom'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getBoxshadowPositionOptions()
    {
        return [
            [
                'label' => __('Outline'),
                'value' => 'outline'
            ],
            [
                'label' => __('Inset'),
                'value' => 'inset'
            ]
        ];
    }
}

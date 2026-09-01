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
use Magezon\UiBuilder\Data\Form\Element\Factory;
use Magezon\UiBuilder\Data\Form\Element\CollectionFactory;

class Advanced extends AbstractModifier
{
    /**
     * @var \Magezon\PopupBuilder\Model\Source\AnimateIn
     */
    protected $animateIn;

    /**
     * @var \Magezon\PopupBuilder\Model\Source\AnimateOut
     */
    protected $animateOut;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @param Factory                                       $factoryElement
     * @param CollectionFactory                             $factoryCollection
     * @param \Magento\Framework\Registry                   $registry
     * @param \Magezon\PopupBuilder\Model\Source\AnimateIn  $animateIn
     * @param \Magezon\PopupBuilder\Model\Source\AnimateOut $animateOut
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        \Magento\Framework\Registry $registry,
        \Magezon\PopupBuilder\Model\Source\AnimateIn $animateIn,
        \Magezon\PopupBuilder\Model\Source\AnimateOut $animateOut
    ) {
        parent::__construct($factoryElement, $factoryCollection, $registry);
        $this->animateIn = $animateIn;
        $this->animateOut = $animateOut;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
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
        $this->addChildren(
            'width',
            'text',
            [
                'label'          => __('Width'),
                'sortOrder'      => 10,
                'additionalInfo' => __('Set the popup width (px).')
            ]
        );

        $this->addChildren(
            'height',
            'select',
            [
                'sortOrder'      => 20,
                'options'        => $this->getHeightOptions(),
                'value'          => 'auto',
                'label'          => __('Height'),
                'additionalInfo' => __('Set the popup height.'),
                'groupsConfig'   => [
                    'custom' => [
                        'custom_height'
                    ]
                ]
            ]
        );

        $this->addChildren(
            'custom_height',
            'number',
            [
                'label'     => __('Custom Height'),
                'sortOrder' => 30,
                'required'  => true
            ]
        );

        $this->addChildren(
            'content_position',
            'select',
            [
                'label'          => __('Content Position'),
                'sortOrder'      => 40,
                'value'          => 'top',
                'options'        => $this->getContentPositionOptions(),
                'additionalInfo' => __('Set position of the content in the popup.')
            ]
        );

        $this->addChildren(
            'position',
            'select',
            [
                'label'          => __('Position'),
                'sortOrder'      => 50,
                'value'          => 'center-center',
                'options'        => $this->getPositionOptions(),
                'additionalInfo' => __('Set position of the popup on a page.')
            ]
        );

        $this->addChildren(
            'overlay',
            'boolean',
            [
                'label'          => __('Overlay'),
                'sortOrder'      => 60,
                'value'          => 1,
                'additionalInfo' => __('Show/hide the background overlay.')
            ]
        );

        $this->addChildren(
            'close_button',
            'boolean',
            [
                'label'          => __('Close Button'),
                'sortOrder'      => 70,
                'value'          => 1,
                'additionalInfo' => __('Show/hide the close button.')
            ]
        );

        $this->addChildren(
            'entrance_animation',
            'select',
            [
                'label'          => __('Entrance Animation'),
                'sortOrder'      => 80,
                'options'        => $this->animateIn->getOptions(),
                'selectType'     => 'optgroup',
                'multiple'       => false,
                'filterOptions'  => true,
                'placeholder'    => __('None'),
                'component'      => 'Magezon_UiBuilder/js/form/element/animation',
                'additionalInfo' => __('Select one from multiple animation effects when the popup appears.')
            ]
        );

        $this->addChildren(
            'exit_animation',
            'select',
            [
                'label'          => __('Exit Animation'),
                'sortOrder'      => 90,
                'options'        => $this->animateOut->getOptions(),
                'selectType'     => 'optgroup',
                'multiple'       => false,
                'filterOptions'  => true,
                'placeholder'    => __('None'),
                'component'      => 'Magezon_UiBuilder/js/form/element/animation',
                'additionalInfo' => __('Select one from multiple animation effects when the popup exits.')
            ]
        );

        $this->addChildren(
            'animation_duration',
            'number',
            [
                'label'          => __('Animation Duration'),
                'sortOrder'      => 100,
                'value'          => 1,
                'additionalInfo' => __('Set the time duration for the animation (seconds).')
            ]
        );
        $this->addChildren(
            'close_button_delay',
            'text',
            [
                'label'          => __('Show Close Button After X seconds'),
                'sortOrder'      => 110,
                'additionalInfo' => __('Set after how many seconds the close button will appear.'),
                'imports'        => [
                    'visible' => '${ $.provider }:${ $.parentScope }.close_button'
                ]
            ]
        );

        $this->addChildren(
            'close_automatically',
            'text',
            [
                'label'          => __('Automatically Close After X seconds'),
                'sortOrder'      => 120,
                'additionalInfo' => __('Set after how many seconds the popup is closed automatically.')
            ]
        );

        $this->addChildren(
            'prevent_close_on_background_click',
            'boolean',
            [
                'label'     => __('Prevent Closing on Overlay'),
                'sortOrder' => 130,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.overlay',
                    '__disableTmpl' => ['visible' => false]
                ],
                'additionalInfo' => __('If Yes, the popup won’t be closed even if the user clicks on the background overlay.')
            ]
        );

        $this->addChildren(
            'prevent_close_on_esc_key',
            'boolean',
            [
                'label'          => __('Prevent Closing on ESC key'),
                'sortOrder'      => 140,
                'additionalInfo' => __('If Yes, the popup won’t be closed even if the user presses ESC key.')
            ]
        );

        $this->addChildren(
            'prevent_scroll',
            'boolean',
            [
                'label'          => __('Disable Page Scrolling'),
                'sortOrder'      => 150,
                'additionalInfo' => __('Set to Yes to prevent page scrolling when the popup is open.')
            ]
        );

        $this->addChildren(
            'avoid_multiple_popups',
            'boolean',
            [
                'label'          => __('Avoid Multiple Popups'),
                'sortOrder'      => 160,
                'additionalInfo' => __('If the user has seen another popup on the page hide this popup.')
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
                'label' => __('Top Left'),
                'value' => 'top-left'
            ],
            [
                'label' => __('Top Center'),
                'value' => 'top-center'
            ],
            [
                'label' => __('Top Right'),
                'value' => 'top-right'
            ],
            [
                'label' => __('Center Left'),
                'value' => 'center-left'
            ],
            [
                'label' => __('Center Center'),
                'value' => 'center-center'
            ],
            [
                'label' => __('Center Right'),
                'value' => 'center-right'
            ],
            [
                'label' => __('Bottom Left'),
                'value' => 'bottom-left'
            ],
            [
                'label' => __('Bottom Center'),
                'value' => 'bottom-center'
            ],
            [
                'label' => __('Bottom Right'),
                'value' => 'bottom-right'
            ]
        ];
    }

    /**
     * @return $this
     */
    protected function createPanel()
    {
        $expand = false;
        $children = $this->getChildren();
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                'advanced' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label'                           => __('Advanced'),
                                'componentType'                   => Fieldset::NAME,
                                'collapsible'                     => true,
                                'initializeFieldsetDataByDefault' => $expand,
                                'opened'                          => $expand,
                                'sortOrder'                       => 50,
                                'dataScope'                       => 'data.display_settings',
                                'additionalClasses'               => 'mgz-popupbuilder-triggers'
                            ]
                        ]
                    ],
                    'children' => $children
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

    /**
     * @return array
     */
    public function getHeightOptions()
    {
        return [
            [
                'label' => __('Fit To Content'),
                'value' => 'auto'
            ],
            [
                'label' => __('Fit To Screen'),
                'value' => 'fit_to_screen'
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
    public function getContentPositionOptions()
    {
        return [
            [
                'label' => __('Top'),
                'value' => 'top'
            ],
            [
                'label' => __('Center'),
                'value' => 'center'
            ],
            [
                'label' => __('Bottom'),
                'value' => 'bottom'
            ]
        ];
    }
}

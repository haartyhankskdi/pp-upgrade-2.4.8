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

class OverlayStyle extends AbstractModifier
{
    const FIELD_BACKGROUND_COLOR           = 'background_color';
    const FIELD_BACKGROUND_IMAGE           = 'background_image';
    const FIELD_BACKGROUND_POSITION        = 'background_position';
    const FIELD_CUSTOM_BACKGROUND_POSITION = 'custom_background_position';
    const FIELD_BACKGROUND_REPEAT          = 'background_repeat';
    const FIELD_BACKGROUND_SIZE            = 'background_size';
    const FIELD_CUSTOM_BACKGROUND_SIZE     = 'custom_background_size';

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
     * Create Editor panel
     *
     * @return $this
     * @since 101.0.0
     */
    protected function createPanel()
    {
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                'style' => [
                    'children' => [
                        'overlay' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Overlay'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'sortOrder'                       => 20,
                                        'dataScope'                       => 'data.style_settings.overlay'
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
            self::FIELD_BACKGROUND_COLOR,
            'color',
            [
                'label'     => __('Background Color'),
                'sortOrder' => 10,
                'rgb'       => true
            ]
        );

        $this->addChildren(
            self::FIELD_BACKGROUND_IMAGE,
            'image',
            [
                'label'     => __('Background Image'),
                'sortOrder' => 20
            ]
        );

        $this->addChildren(
            self::FIELD_BACKGROUND_POSITION,
            'select',
            [
                'label'        => __('Background Position'),
                'sortOrder'    => 30,
                'options'      => $this->getBackgroundPositionOptions(),
                'placeholder'  => __('Default'),
                'groupsConfig' => [
                'custom'       => [
                        self::FIELD_CUSTOM_BACKGROUND_POSITION
                    ]
                ]
            ]
        );

        $this->addChildren(
            self::FIELD_CUSTOM_BACKGROUND_POSITION,
            'text',
            [
                'label'       => __('Custom Background Position'),
                'sortOrder'   => 40
            ]
        );

        $this->addChildren(
            self::FIELD_BACKGROUND_REPEAT,
            'select',
            [
                'label'       => __('Background Repeat'),
                'sortOrder'   => 50,
                'options'     => $this->getBackgroundRepeatOptions(),
                'placeholder' => __('Default')
            ]
        );

        $this->addChildren(
            self::FIELD_BACKGROUND_SIZE,
            'select',
            [
                'label'        => __('Background Size'),
                'sortOrder'    => 60,
                'options'      => $this->getBackgroundSizeOptions(),
                'placeholder'  => __('Default'),
                'groupsConfig' => [
                    'custom' => [
                        self::FIELD_CUSTOM_BACKGROUND_SIZE
                    ]
                ]
            ]
        );

        $this->addChildren(
            self::FIELD_CUSTOM_BACKGROUND_SIZE,
            'text',
            [
                'label'     => __('Custom Background Size'),
                'sortOrder' => 70
            ]
        );
    }

    /**
     * @return array
     */
    public function getBackgroundPositionOptions()
    {
        return [
            [
                'label' => __('Top Left'),
                'value' => 'top left'
            ],
            [
                'label' => __('Top Center'),
                'value' => 'top center'
            ],
            [
                'label' => __('Top Right'),
                'value' => 'top right'
            ],
            [
                'label' => __('Center Left'),
                'value' => 'center left'
            ],
            [
                'label' => __('Center Center'),
                'value' => 'center center'
            ],
            [
                'label' => __('Center Right'),
                'value' => 'center right'
            ],
            [
                'label' => __('Bottom Left'),
                'value' => 'bottom left'
            ],
            [
                'label' => __('Bottom Center'),
                'value' => 'bottom center'
            ],
            [
                'label' => __('Bottom Right'),
                'value' => 'bottom right'
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
    public function getBackgroundRepeatOptions()
    {
        return [
            [
                'label' => __('No-repeat'),
                'value' => 'no-repeat'
            ],
            [
                'label' => __('Repeat'),
                'value' => 'repeat'
            ],
            [
                'label' => __('Repeat-x'),
                'value' => 'repeat-x'
            ],
            [
                'label' => __('Repeat-y'),
                'value' => 'repeat-y'
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
}

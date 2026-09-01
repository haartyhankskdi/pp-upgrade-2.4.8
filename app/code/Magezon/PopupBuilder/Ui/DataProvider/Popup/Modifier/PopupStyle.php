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

class PopupStyle extends \Magezon\UiBuilder\Ui\DataProvider\Form\Modifier\Styling
{
    /**
     * @var array
     */
    protected $meta;

    protected function createStylingPanel()
    {
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                'style' => [
                    'children' => [
                        'popup' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Popup'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'sortOrder'                       => 10,
                                        'additionalClasses'               => 'uibuilder-styling',
                                        'template'                        => 'Magezon_UiBuilder/form/edit/styling',
                                        'dataScope'                       => 'data.style_settings.popup'
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
}

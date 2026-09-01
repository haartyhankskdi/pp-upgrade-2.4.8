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

class Conditions extends \Magezon\UiChooserLayout\Ui\DataProvider\Form\Modifier\Conditions
{
    /**
     * @var array
     */
    protected $meta;

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
                        static::GROUP_GENERAL => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Conditions'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'opened'                          => false,
                                        'sortOrder'                       => 10,
                                        'dataScope'                       => 'data.conditions'
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
}

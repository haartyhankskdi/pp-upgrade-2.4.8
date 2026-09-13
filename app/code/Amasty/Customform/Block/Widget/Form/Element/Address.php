<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */
/**
 * Copyright В© 2016 Amasty. All rights reserved.
 */
namespace Amasty\Customform\Block\Widget\Form\Element;

class Address extends AbstractElement
{
    public function _construct()
    {
        parent::_construct();

        $this->options['title'] = __('Address');
    }

    public function generateContent()
    {
        return '<h3 class="title">' . __('Address, City, State / Province / Region, Zipcode, Country') . '</h3>';
    }

    /**
     * @param $type
     * @param $parentType
     * @return array
     */
    public function getElementData($type, $parentType)
    {
        $result = parent::getElementData($type, $parentType);
        $result['childs'] = $this->getChildElements();

        return $result;
    }

    /**
     * @return array
     */
    private function getChildElements()
    {
        return [
            [
                'type' => 'textinput',
                'data' =>
                    [
                        'label' => __('Address'),
                        'entityType' => 'address'
                    ]
            ],
            [
                'type' => 'textinput',
                'data' =>
                    [
                        'label' => __('City'),
                        'layout' => 'two',
                        'entityType' => 'address'
                    ]
            ],
            [
                'type' => 'textinput',
                'data' =>
                [
                    'label' => __('State / Province / Region'),
                    'layout' => 'two',
                    'entityType' => 'address'
                ]
            ],
            [
                'type' => 'textinput',
                'data' =>
                    [
                        'label' => __('Zipcode'),
                        'layout' => 'two',
                        'entityType' => 'address'
                    ]
            ],
            [
                'type' => 'country',
                'data' =>
                    [
                        'label' => __('Country'),
                        'layout' => 'two',
                        'entityType' => 'address'
                    ]
            ],
        ];
    }
}

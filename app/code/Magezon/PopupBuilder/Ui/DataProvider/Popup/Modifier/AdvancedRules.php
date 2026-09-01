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

class AdvancedRules extends AbstractModifier
{
    /**
     * @var \Magezon\PopupBuilder\Model\Source\SalesRuleList
     */
    protected $salesRuleList;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @param Factory                                          $factoryElement
     * @param CollectionFactory                                $factoryCollection
     * @param \Magento\Framework\Registry                      $registry
     * @param \Magezon\PopupBuilder\Model\Source\SalesRuleList $salesRuleList
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        \Magento\Framework\Registry $registry,
        \Magezon\PopupBuilder\Model\Source\SalesRuleList $salesRuleList
    ) {
        parent::__construct($factoryElement, $factoryCollection, $registry);
        $this->salesRuleList = $salesRuleList;
    }

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
                'label'             => __('Show after X page views'),
                'sortOrder'         => 10,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container1->addChildren(
            'page_views',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, set the number of page views before the popup is triggered.')
                ]
            ]
        );

        $container1->addChildren(
            'page_views_views',
            'number',
            [
                'label'     => __('Page Views'),
                'sortOrder' => 20,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.page_views',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container2 = $this->addContainerGroup(
            'container2',
            [
                'label'             => __('Show after X minutes'),
                'sortOrder'         => 20,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container2->addChildren(
            'minutes',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, set after how many minutes the popup will show again.')
                ]
            ]
        );

        $container2->addChildren(
            'minutes_minutes',
            'number',
            [
                'label'     => __('Minutes'),
                'sortOrder' => 20,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.minutes',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container3 = $this->addContainerGroup(
            'container3',
            [
                'label'             => __('On up to X times'),
                'sortOrder'         => 30,
                'additionalClasses' => 'mgz-popupbuilder-field-inline mgz-popupbuilder-field-margin'

            ]
        );

        $container3->addChildren(
            'times',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('Specify max times the popup will appear.')
                ]
            ]
        );

        $container3->addChildren(
            'times_type',
            'select',
            [
                'label'     => __('Count'),
                'sortOrder' => 20,
                'required'  => true,
                'options'   => $this->getCountOptions(),
                'value'     => 'open',
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.times',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container3->addChildren(
            'times_times',
            'number',
            [
                'label'     => __('Times'),
                'sortOrder' => 30,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.times',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container4 = $this->addContainerGroup(
            'container4',
            [
                'label'             => __('When arriving from specific URL'),
                'sortOrder'         => 40,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container4->addChildren(
            'url',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, Show or Hide the popup if the user arrives from a specific URL.')
                ]
            ]
        );

        $container4->addChildren(
            'url_action',
            'select',
            [
                'label'     => __('Action'),
                'sortOrder' => 20,
                'required'  => true,
                'options'   => $this->getUrlActionOptions(),
                'value'     => 'show',
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.url',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container4->addChildren(
            'url_url',
            'text',
            [
                'label'     => __('URL'),
                'sortOrder' => 30,
                'required'  => true,
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.url',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container5 = $this->addContainerGroup(
            'container5',
            [
                'label'             => __('Show when arriving from'),
                'sortOrder'         => 50,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container5->addChildren(
            'sources',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, the popup will show if the users arrives from Search Engines, External Links and/or Internal Links.')
                ]
            ]
        );

        $container5->addChildren(
            'sources_sources',
            'select',
            [
                'sortOrder' => 20,
                'required'  => true,
                'options'   => $this->getSourcesOptions(),
                'multiple'  => true,
                'value'     => ['search', 'external', 'internal'],
                'imports'   => [
                    'visible' => '${ $.provider }:${ $.parentScope }.sources',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $container6 = $this->addContainerGroup(
            'container6',
            [
                'label'             => __('Show on devices'),
                'sortOrder'         => 60,
                'additionalClasses' => 'mgz-popupbuilder-field-inline'

            ]
        );

        $container6->addChildren(
            'devices',
            'boolean',
            [
                'sortOrder'    => 10,
                'required'     => true,
                'labelVisible' => false,
                'tooltip'      => [
                    'description' => __('If Yes, choose to show the popup on Desktop, Tablet Landscape, Tablet Portrait, Mobile Landscape and/or Mobile Portrait.')
                ]
            ]
        );

        $container6->addChildren(
            'devices_devices',
            'select',
            [
                'labelVisible' => false,
                'multiple'     => true,
                'sortOrder'    => 20,
                'options'      => $this->getDeviceOptions(),
                'value'        => ['xl', 'lg', 'md', 'sm', 'xs'],
                'imports'      => [
                    'visible' => '${ $.provider }:${ $.parentScope }.devices',
                    '__disableTmpl' => ['visible' => false]
                ]
            ]
        );

        $this->addChildren(
            'hide_subscribers',
            'boolean',
            [
                'sortOrder' => 70,
                'label'     => __('Hide from existing subscribers'),
                'tooltip'   => [
                    'description' => __('If Yes, the popup will be hidden from existing subscribers of your site.')
                ]
            ]
        );

        $this->addChildren(
            'salerule_id',
            'select',
            [
                'sortOrder' => 80,
                'label'     => __('Sale Rules'),
                'multiple'  => true,
                'options'   => $this->salesRuleList->toOptionArray(),
                'tooltip'   => [
                    'description' => __('The popup will be displayed if specific cart price rules are met.')
                ]
            ]
        );

        $this->addChildren(
            'days_of_week',
            'select',
            [
                'sortOrder' => 90,
                'label'     => __('Days Of The Week'),
                'multiple'  => true,
                'options'   => $this->getDaysOfWeek()
            ]
        );

        $this->addChildren(
            'from_hour',
            'select',
            [
                'sortOrder'   => 100,
                'label'       => __('From hour'),
                'options'     => $this->getRange(0, 23, 1, true),
                'placeholder' => __('None')
            ]
        );

        $this->addChildren(
            'to_hour',
            'select',
            [
                'sortOrder'   => 110,
                'label'       => __('To hour'),
                'options'     => $this->getRange(0, 23, 1, true),
                'placeholder' => __('None')
            ]
        );
    }

    /**
     * Create Editor panel
     *
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
                        'advanced_rules' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label'                           => __('Advanced Rules'),
                                        'componentType'                   => Fieldset::NAME,
                                        'collapsible'                     => true,
                                        'initializeFieldsetDataByDefault' => false,
                                        'opened'                          => false,
                                        'sortOrder'                       => 30,
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
    public function getCountOptions()
    {
        return [
            [
                'label' => __('On Open'),
                'value' => 'open'
            ],
            [
                'label' => __('On Close'),
                'value' => 'close'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getUrlActionOptions()
    {
        return [
            [
                'label' => __('Show'),
                'value' => 'show'
            ],
            [
                'label' => __('Hide'),
                'value' => 'hide'
            ],
            [
                'label' => __('Regex'),
                'value' => 'regex'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getSourcesOptions()
    {
        return [
            [
                'label' => __('Search Engines'),
                'value' => 'search'
            ],
            [
                'label' => __('External Links'),
                'value' => 'external'
            ],
            [
                'label' => __('Internal Links'),
                'value' => 'internal'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getDeviceOptions()
    {
        return [
            [
                'label' => __('Desktop'),
                'value' => 'xl'
            ],
            [
                'label' => __('Tablet Landscape'),
                'value' => 'lg'
            ],
            [
                'label' => __('Tablet Portrait'),
                'value' => 'md'
            ],
            [
                'label' => __('Mobile Landscape'),
                'value' => 'sm'
            ],
            [
                'label' => __('Mobile Portrait'),
                'value' => 'xs'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getDaysOfWeek()
    {
        return [
            [
                'label' => __('Monday'),
                'value' => 1
            ],
            [
                'label' => __('Tuesday'),
                'value' => 2
            ],
            [
                'label' => __('Wednesday'),
                'value' => 3
            ],
            [
                'label' => __('Thursday'),
                'value' => 4
            ],
            [
                'label' => __('Friday'),
                'value' => 5
            ],
            [
                'label' => __('Saturday'),
                'value' => 6
            ],
            [
                'label' => __('Sunday'),
                'value' => 0
            ]
        ];
    }

    /**
     * @return array
     */
    public function getHourOptions()
    {
        return [
            [
                'label' => __('Monday'),
                'value' => 1
            ],
            [
                'label' => __('Tuesday'),
                'value' => 2
            ],
            [
                'label' => __('Wednesday'),
                'value' => 3
            ],
            [
                'label' => __('Thursday'),
                'value' => 4
            ],
            [
                'label' => __('Friday'),
                'value' => 5
            ],
            [
                'label' => __('Saturday'),
                'value' => 6
            ],
            [
                'label' => __('Sunday'),
                'value' => 0
            ]
        ];
    }

    /**
     * @param  int  $from
     * @param  int  $to
     * @param  boolean $prefix
     * @return array
     */
    public function getRange($from, $to, $step = 1, $prefix = false, $suffix = '')
    {
        $options = [];
        for ($i = $from; $i <= $to; $i) {
            $label = $value = $i;
            if ($i < 10 && $prefix) {
                $label = '0' . $label;
            }
            $options[] = [
                'label' => $label . $suffix,
                'value' => $value . ''
            ];
            $i+=$step;
        }
        return $options;
    }
}

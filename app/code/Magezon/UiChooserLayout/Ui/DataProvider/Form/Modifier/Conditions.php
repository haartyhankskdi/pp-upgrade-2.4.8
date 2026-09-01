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
 * @package   Magezon_UiChooserLayout
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\UiChooserLayout\Ui\DataProvider\Form\Modifier;

use Magento\Ui\Component\Form\Fieldset;
use Magezon\UiBuilder\Data\Form\Element\Factory;
use Magezon\UiBuilder\Data\Form\Element\CollectionFactory;

class Conditions extends \Magezon\UiBuilder\Ui\DataProvider\Form\AbstractModifier
{
    const GROUP_GENERAL                    = 'conditions_container';
    const GROUP_GENERAL_DEFAULT_SORT_ORDER = 0;
    const GROUP_CONDITIONAL_NAME           = 'conditions';
    const FIELD_IS_DELETE                  = 'is_delete';

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magezon\UiChooserLayout\Model\PageGroupProvider
     */
    protected $pageGroupProvider;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @param Factory                                          $factoryElement    
     * @param CollectionFactory                                $factoryCollection 
     * @param \Magento\Framework\View\Asset\Repository         $assetRepo         
     * @param \Magento\Backend\Model\UrlInterface              $urlBuilder        
     * @param \Magento\Framework\App\RequestInterface          $request           
     * @param \Magezon\UiChooserLayout\Model\PageGroupProvider $pageGroupProvider 
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request,
        \Magezon\UiChooserLayout\Model\PageGroupProvider $pageGroupProvider
    ) {
        parent::__construct($factoryElement, $factoryCollection);
        $this->assetRepo         = $assetRepo;
        $this->urlBuilder        = $urlBuilder;
        $this->request           = $request;
        $this->pageGroupProvider = $pageGroupProvider;
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
        $container1 = $this->addChildren(static::GROUP_CONDITIONAL_NAME, 'dynamicRows', [
            'deleteProperty'      => 'condition_delete',
            'deleteValue'         => '1',
            'addButton'           => true,
            'renderDefaultRecord' => false,
            'columnsHeader'       => false,
            'collapsibleHeader'   => true,
            'sortOrder'           => 10,
            'additionalClasses'   => 'admin__field-wide mgz-uichooserlayout',
            'addButtonLabel'      => __('Add Condition')
        ]);

            $container2 = $container1->addContainer('record', [
                'headerLabel'   => __('New Condition'),
                'component'     => 'Magento_Ui/js/dynamic-rows/record',
                'isTemplate'    => true,
                'is_collection' => true
            ]);

                $container3 = $container2->addContainerGroup(
                    'container3',
                    [
                        'sortOrder'         => 10,
                        'additionalClasses' => 'mgz-uichooserlayout-row'
                    ]
                );

                    $container3->addChildren(
                        'status',
                        'select',
                        [
                            'sortOrder'         => 10,
                            'options'           => $this->getSkins(),
                            'value'             => 'include',
                            'additionalClasses' => 'mgz-uichooserlayout-multiselect2',
                            'labelVisible'      => false
                        ]
                    );

                    $container3->addChildren(
                        'page_group',
                        'select',
                        [
                            'sortOrder'     => 20,
                            'options'       => $this->pageGroupProvider->getOptions(),
                            'selectType'    => 'optgroup',
                            'multiple'      => false,
                            'filterOptions' => true,
                            'labelVisible'  => false,
                            'required'      => true,
                            'showRemove'    => false,
                            'placeholder'   => __('-- Please Select --')
                        ]
                    );

                    $container3->addChildren(
                        'page_group_type',
                        'select',
                        [
                            'sortOrder'         => 30,
                            'options'           => $this->getPageGroupTypes(),
                            'component'         => 'Magezon_UiChooserLayout/js/form/element/page-group-type',
                            'pageLayouts'       => $this->pageGroupProvider->getOptions(),
                            'selectType'        => 'optgroup',
                            'multiple'          => false,
                            'value'             => 'all',
                            'labelVisible'      => false,
                            'required'          => true,
                            'additionalClasses' => 'mgz-uichooserlayout-multiselect2',
                            'imports'           => [
                                'pageGroup'     => '${ $.provider }:${ $.parentScope }.page_group',
                                'pageGroupType' => '${ $.provider }:${ $.parentScope }.page_group_type',
                                '__disableTmpl' => ['pageGroup' => false, 'pageGroupType' => false]
                            ]
                        ]
                    );

                    $container3->addChildren(
                        'entities',
                        'text',
                        [
                            'sortOrder'         => 40,
                            'component'         => 'Magezon_UiChooserLayout/js/form/element/chooser',
                            'elementTmpl'       => 'Magezon_UiChooserLayout/form/element/chooser',
                            'pageLayouts'       => $this->pageGroupProvider->getOptions(),
                            'additionalClasses' => 'mgz-uichooserlayout-chooser',
                            'labelVisible'      => false,
                            'required'          => true,
                            'chooserImageSrc'   => $this->getViewFileUrl('images/rule_chooser_trigger.gif'),
                            'chooserApplySrc'   => $this->getViewFileUrl('images/rule_component_apply.gif'),
                            'imports'           => [
                                'pageGroup'     => '${ $.provider }:${ $.parentScope }.page_group',
                                'pageGroupType' => '${ $.provider }:${ $.parentScope }.page_group_type',
                                '__disableTmpl' => ['pageGroup' => false, 'pageGroupType' => false]
                            ]
                        ]
                    );

                $container2->addChildren(static::FIELD_IS_DELETE, 'actionDelete', [
                    'fit'       => true,
                    'sortOrder' => 3000
                ]);
    }


    /**
     * @return array
     */
    public function getSkins()
    {
        return [
            [
                'label' => __('Include'),
                'value' => 'include'
            ],
            [
                'label' => __('Exclude'),
                'value' => 'exclude'
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPageGroupTypes()
    {
        return [
            [
                'label' => __('All'),
                'value' => 'all'
            ],
            [
                'label' => __('Specific'),
                'value' => 'specific'
            ]
        ];
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
                static::GROUP_GENERAL => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label'                           => __('Conditions'),
                                'componentType'                   => Fieldset::NAME,
                                'collapsible'                     => true,
                                'initializeFieldsetDataByDefault' => false,
                                'opened'                          => false,
                                'sortOrder'                       => static::GROUP_GENERAL_DEFAULT_SORT_ORDER,
                                'dataScope'                       => 'data'
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
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    protected function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }
}

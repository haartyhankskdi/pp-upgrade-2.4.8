<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render\Category;
use Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render\ProductConditions;
use Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render\Snippet;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\Page;
use Mageplaza\PromoBanner\Model\Config\Source\Position;

/**
 * Class Display
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab
 */
class Display extends Generic implements TabInterface
{
    /**
     * @var Position
     */
    protected $positionConfig;

    /**
     * @var Page
     */
    protected $displayPage;

    /**
     * @var Yesno
     */
    protected $yesNo;

    /**
     * @var FieldFactory
     */
    protected $_fieldFactory;

    /**
     * Display constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Position $positionConfig
     * @param Page $displayPage
     * @param Yesno $yesNo
     * @param FieldFactory $fieldFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Position $positionConfig,
        Page $displayPage,
        Yesno $yesNo,
        FieldFactory $fieldFactory,
        array $data = []
    ) {
        $this->positionConfig = $positionConfig;
        $this->displayPage    = $displayPage;
        $this->yesNo          = $yesNo;
        $this->_fieldFactory  = $fieldFactory;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return mixed
     */
    public function getCurrentBanner()
    {
        return $this->_coreRegistry->registry('mppromobanner_banner');
    }

    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var Banner $model */
        $model = $this->getCurrentBanner();
        $form  = $this->_formFactory->create();
        $form->setHtmlIdPrefix('mppromobanner_');
        $form->setFieldNameSuffix('mppromobanner');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Display')
            ]
        );

        $selectPosition = $fieldset->addField('position', 'select', [
            'name'   => 'position',
            'label'  => __('Display Position'),
            'title'  => __('Display Position'),
            'values' => $this->positionConfig->toOptionArray()
        ]);

        $selectPage = $fieldset->addField('page', 'select', [
            'name'   => 'page',
            'label'  => __('Select Page(s)'),
            'title'  => __('Select Page(s)'),
            'values' => [
                ['value' => 0, 'label' => __('All Pages')],
                ['value' => 1, 'label' => __('Specific Pages')]
            ]
        ]);

        $pageType = $fieldset->addField('page_type', 'multiselect', [
            'name'   => 'page_type',
            'label'  => __('Display on Page(s)'),
            'title'  => __('Display on Page(s)'),
            'values' => $this->displayPage->toOptionArray(),
            'note'   => __('Compatible <a href="https://www.mageplaza.com/magento-2-one-step-checkout-extension/" target="_blank" rel="noopener noreferrer">Mageplaza One Step Checkout</a>')
        ]);

        $categoryPage = $fieldset->addField('category_ids', Category::class, [
            'name'  => 'category_ids',
            'label' => __('Display On Category Page'),
            'title' => __('Display On Category Page'),
        ]);

        $showProductPage = $fieldset->addField('show_product_page', 'select', [
            'name'   => 'show_product_page',
            'label'  => __('Display On Product Page'),
            'title'  => __('Display On Product Page'),
            'values' => $this->yesNo->toOptionArray()
        ]);

        $newChildUrl = $this->getUrl(
            'mppromobanner/condition/newActionHtml/form/mppromobanner_actions',
            ['form_namespace' => 'mppromobanner_form']
        );

        $productCondition = $fieldset->addField('actions', ProductConditions::class, [
            'name'           => 'actions',
            'label'          => __('Select'),
            'title'          => __('Select'),
            'data-form-part' => 'mppromobanner_form'
        ])->setNewChildUrl($newChildUrl);

        $snippetCode = $fieldset->addField(
            'snippet_code',
            Snippet::class,
            ['name' => 'snippet_code', 'subject' => $this]
        );

        $refField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            Position::PAGE_TOP,
                            Position::CONTENT_TOP,
                            Position::SIDEBAR_ADDITIONAL,
                            Position::SIDEBAR_MAIN,
                            Position::UNDER_ADD_TO_CART_BUTTON,
                            Position::LEFT_FLOATING,
                            Position::RIGHT_FLOATING,
                            Position::POPUP
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $categoryRefField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            Position::PAGE_TOP,
                            Position::CONTENT_TOP,
                            Position::SIDEBAR_ADDITIONAL,
                            Position::SIDEBAR_MAIN,
                            Position::LEFT_FLOATING,
                            Position::RIGHT_FLOATING,
                            Position::POPUP
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $productRefField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            Position::PAGE_TOP,
                            Position::CONTENT_TOP,
                            Position::UNDER_ADD_TO_CART_BUTTON,
                            Position::LEFT_FLOATING,
                            Position::RIGHT_FLOATING,
                            Position::POPUP
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $pageRefField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            Position::PAGE_TOP,
                            Position::CONTENT_TOP,
                            Position::LEFT_FLOATING,
                            Position::RIGHT_FLOATING,
                            Position::POPUP
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $dependencies = $this->getLayout()->createBlock(Dependence::class)
            ->addFieldMap($selectPosition->getHtmlId(), $selectPosition->getName())
            ->addFieldMap($selectPage->getHtmlId(), $selectPage->getName())
            ->addFieldMap($pageType->getHtmlId(), $pageType->getName())
            ->addFieldMap($categoryPage->getHtmlId(), $categoryPage->getName())
            ->addFieldMap($showProductPage->getHtmlId(), $showProductPage->getName())
            ->addFieldMap($productCondition->getHtmlId(), $productCondition->getName())
            ->addFieldMap($snippetCode->getHtmlId(), $snippetCode->getName())
            ->addFieldDependence($selectPage->getName(), $selectPosition->getName(), $refField)
            ->addFieldDependence($snippetCode->getName(), $selectPosition->getName(), Position::SNIPPET_CODE)
            ->addFieldDependence($pageType->getName(), $selectPosition->getName(), $pageRefField)
            ->addFieldDependence($showProductPage->getName(), $selectPosition->getName(), $productRefField)
            ->addFieldDependence($productCondition->getName(), $selectPosition->getName(), $productRefField)
            ->addFieldDependence($categoryPage->getName(), $selectPosition->getName(), $categoryRefField)
            ->addFieldDependence($pageType->getName(), $selectPage->getName(), '1')
            ->addFieldDependence($categoryPage->getName(), $selectPage->getName(), '1')
            ->addFieldDependence($showProductPage->getName(), $selectPage->getName(), '1')
            ->addFieldDependence($productCondition->getName(), $selectPage->getName(), '1')
            ->addFieldDependence($productCondition->getName(), $showProductPage->getName(), '1');

        // define field dependencies
        $this->setChild('form_after', $dependencies);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Display');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}

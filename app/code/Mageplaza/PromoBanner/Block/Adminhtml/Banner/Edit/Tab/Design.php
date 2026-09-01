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
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Cms\Model\ResourceModel\Block\Collection as CmsCollection;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render\Image as BannerImage;
use Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab\Render\SliderImages;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\PopupResponsive;
use Mageplaza\PromoBanner\Model\Config\Source\Type as BannerType;

/**
 * Class Design
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab
 */
class Design extends Generic implements TabInterface
{
    /**
     * @var WysiwygConfig
     */
    protected $_wysiwygConfig;

    /**
     * @var Fieldset
     */
    protected $_rendererFieldset;

    /**
     * @var FieldFactory
     */
    protected $_fieldFactory;

    /**
     * @var BannerType
     */
    protected $bannerType;

    /**
     * @var HelperImage
     */
    protected $imageHelper;

    /**
     * @var CmsCollection
     */
    protected $cmsCollection;

    /**
     * @var PopupResponsive
     */
    protected $popupResponsive;

    /**
     * Design constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param WysiwygConfig $wysiwygConfig
     * @param Fieldset $rendererFieldset
     * @param FieldFactory $fieldFactory
     * @param BannerType $bannerType
     * @param HelperImage $imageHelper
     * @param CmsCollection $cmsCollection
     * @param PopupResponsive $popupResponsive
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        WysiwygConfig $wysiwygConfig,
        Fieldset $rendererFieldset,
        FieldFactory $fieldFactory,
        BannerType $bannerType,
        HelperImage $imageHelper,
        CmsCollection $cmsCollection,
        PopupResponsive $popupResponsive,
        array $data = []
    ) {
        $this->_wysiwygConfig    = $wysiwygConfig;
        $this->_rendererFieldset = $rendererFieldset;
        $this->_fieldFactory     = $fieldFactory;
        $this->bannerType        = $bannerType;
        $this->imageHelper       = $imageHelper;
        $this->cmsCollection     = $cmsCollection;
        $this->popupResponsive   = $popupResponsive;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var Banner $model */
        $model = $this->_coreRegistry->registry('mppromobanner_banner');
        $form  = $this->_formFactory->create();
        $form->setHtmlIdPrefix('mppromobanner_');
        $form->setFieldNameSuffix('mppromobanner');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Design')
            ]
        );

        $type = $fieldset->addField('type', 'select', [
            'name'   => 'type',
            'label'  => __('Promo Banner Type'),
            'title'  => __('Promo Banner Type'),
            'values' => $this->bannerType->toOptionArray()
        ]);

        $singleImage = $fieldset->addField('banner_image', BannerImage::class, [
            'name'  => 'banner_image',
            'label' => __('Select Image'),
            'title' => __('Select Image'),
            'path'  => $this->imageHelper->getBaseMediaPath(HelperImage::TEMPLATE_MEDIA_TYPE_BANNER)
        ]);

        $sliderImages = $fieldset->addField('slider_images', SliderImages::class, [
            'name'  => 'slider_images',
            'label' => __('Select Image(s)'),
            'title' => __('Select Image(s)'),
        ]);

        $popupImage = $fieldset->addField('popup_image', BannerImage::class, [
            'name'  => 'popup_image',
            'label' => __('Select Popup Banner'),
            'title' => __('Select Popup Banner'),
            'path'  => $this->imageHelper->getBaseMediaPath(HelperImage::TEMPLATE_MEDIA_TYPE_BANNER)
        ]);

        $popupRes = $fieldset->addField('popup_responsive', 'select', [
            'name'   => 'popup_responsive',
            'label'  => __('Popup Responsive'),
            'title'  => __('Popup Responsive'),
            'values' => $this->popupResponsive->toFormOptionArray()
        ]);

        $floatingImage = $fieldset->addField('floating_image', BannerImage::class, [
            'name'  => 'floating_image',
            'label' => __('Select Floating Banner'),
            'title' => __('Select Floating Banner'),
            'path'  => $this->imageHelper->getBaseMediaPath(HelperImage::TEMPLATE_MEDIA_TYPE_BANNER)
        ]);

        $url = $fieldset->addField('url', 'text', [
            'name'  => 'url',
            'label' => __('Direct URL'),
            'title' => __('Direct URL'),
            'class' => 'validate-url validate-no-html-tags no-whitespace'
        ]);

        $fieldset->addField('content', 'editor', [
            'name'     => 'content',
            'label'    => __('Text Content'),
            'title'    => __('Text Content'),
            'required' => false,
            'config'   => $this->_wysiwygConfig->getConfig([
                'hidden'         => true,
                'add_variables'  => false,
                'add_widgets'    => false,
                'add_images'     => true,
                'add_directives' => true
            ])
        ]);

        $cms = $fieldset->addField('cms_block_id', 'select', [
            'name'   => 'cms_block_id',
            'label'  => __('Select CMS Block'),
            'title'  => __('Select CMS Block'),
            'values' => $this->cmsCollection->toOptionArray()
        ]);

        $previewButton = $this->getLayout()->createBlock(
            Button::class,
            '',
            [
                'data' => [
                    'type'           => 'button',
                    'label'          => __('Preview Banner'),
                    'class'          => 'preview',
                    'data_attribute' => [
                        'role' => 'template-preview',
                    ]
                ]
            ]
        );

        $fieldset->addField('preview_banner', 'note', ['text' => $previewButton->toHtml(), 'label' => '']);

        $refField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            BannerType::SINGLE_IMAGE,
                            BannerType::POPUP,
                            BannerType::FLOATING
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $previewRefField = $this->_fieldFactory->create(
            [
                'fieldData'   =>
                    [
                        'value'     => implode(',', [
                            BannerType::CMS_BLOCK,
                            BannerType::HTML_TEXT
                        ]),
                        'separator' => ','
                    ],
                'fieldPrefix' => ''
            ]
        );

        $dependencies = $this->getLayout()->createBlock(Dependence::class)
            ->addFieldMap($type->getHtmlId(), $type->getName())
            ->addFieldMap($singleImage->getHtmlId(), $singleImage->getName())
            ->addFieldMap($sliderImages->getHtmlId(), $sliderImages->getName())
            ->addFieldMap($cms->getHtmlId(), $cms->getName())
            ->addFieldMap($popupImage->getHtmlId(), $popupImage->getName())
            ->addFieldMap($popupRes->getHtmlId(), $popupRes->getName())
            ->addFieldMap($floatingImage->getHtmlId(), $floatingImage->getName())
            ->addFieldMap($url->getHtmlId(), $url->getName())
            ->addFieldMap($previewButton->getHtmlId(), $previewButton->getName())
            ->addFieldDependence($singleImage->getName(), $type->getName(), BannerType::SINGLE_IMAGE)
            ->addFieldDependence($sliderImages->getName(), $type->getName(), BannerType::SLIDER)
            ->addFieldDependence($cms->getName(), $type->getName(), BannerType::CMS_BLOCK)
            ->addFieldDependence($popupImage->getName(), $type->getName(), BannerType::POPUP)
            ->addFieldDependence($popupRes->getName(), $type->getName(), BannerType::POPUP)
            ->addFieldDependence($floatingImage->getName(), $type->getName(), BannerType::FLOATING)
            ->addFieldDependence($url->getName(), $type->getName(), $refField)
            ->addFieldDependence($previewButton->getName(), $type->getName(), $previewRefField);
        $this->setChild('form_after', $dependencies);

        $form->addValues($model->getData());
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
        return __('General');
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

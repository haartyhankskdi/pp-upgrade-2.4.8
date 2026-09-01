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
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\Config\Source\AutoClose;
use Mageplaza\PromoBanner\Model\Config\Source\Frequency;

/**
 * Class Trigger
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab
 */
class Trigger extends Generic implements TabInterface
{
    /**
     * @var Frequency
     */
    protected $frequency;

    /**
     * @var AutoClose
     */
    protected $autoClose;

    /**
     * Trigger constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Frequency $frequency
     * @param AutoClose $autoClose
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Frequency $frequency,
        AutoClose $autoClose,
        array $data = []
    ) {
        $this->frequency = $frequency;
        $this->autoClose = $autoClose;
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

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Trigger')]);

        $fieldset->addField('auto_close_time', 'select', [
            'name'   => 'auto_close_time',
            'label'  => __('Auto-close after'),
            'title'  => __('Auto-close after'),
            'values' => $this->autoClose->toOptionArrayConfig(),
            'note'   => __('Set the time to auto close promo banners after showing.')
        ]);

        $fieldset->addField('auto_reopen_time', 'select', [
            'name'   => 'auto_reopen_time',
            'label'  => __('Auto-reopen schedule'),
            'title'  => __('Auto-reopen schedule'),
            'values' => $this->frequency->toOptionArrayConfig(),
            'note'   => __('Set the time to reopen promo banners after being closed (when customers click on the close button).')
        ]);

        if (!$model->getAutoCloseTime()) {
            $model->setAutoCloseTime('use_config');
        }
        if (!$model->getAutoReopenTime()) {
            $model->setAutoReopenTime('use_config');
        }

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
        return __('Trigger');
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

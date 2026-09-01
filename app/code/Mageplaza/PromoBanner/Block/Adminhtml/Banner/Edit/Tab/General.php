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

use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Config\Model\Config\Source\Enabledisable;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerCollection;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Mageplaza\PromoBanner\Helper\Data;

/**
 * Class General
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab
 */
class General extends Generic implements TabInterface
{
    /**
     * @var CustomerCollection
     */
    protected $_customerCollection;

    /**
     * @var Store
     */
    protected $systemStore;

    /**
     * @var Enabledisable
     */
    protected $statusOptions;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * General constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param CustomerCollection $customerCollection
     * @param Store $systemStore
     * @param Enabledisable $statusOptions
     * @param Data $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        CustomerCollection $customerCollection,
        Store $systemStore,
        Enabledisable $statusOptions,
        Data $helperData,
        array $data = []
    ) {
        $this->_customerCollection = $customerCollection;
        $this->systemStore         = $systemStore;
        $this->statusOptions       = $statusOptions;
        $this->helperData          = $helperData;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Generic
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('mppromobanner_banner');

        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('mppromobanner_');
        $form->setFieldNameSuffix('mppromobanner');
        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Item Information'),
            'class'  => 'fieldset-wide'
        ]);
        if ($model->getId()) {
            $fieldset->addField('banner_id', 'hidden', ['name' => 'banner_id']);
        }

        $fieldset->addField('name', 'text', [
            'name'     => 'name',
            'label'    => __('Name'),
            'title'    => __('Name'),
            'required' => true
        ]);

        $fieldset->addField('status', 'select', [
            'name'   => 'status',
            'label'  => __('Status'),
            'title'  => __('Status'),
            'values' => $this->statusOptions->toOptionArray()
        ]);
        if (!$model->getId()) {
            $model->setData('status', 1);
        }

        if ($this->_storeManager->isSingleStoreMode()) {
            $fieldset->addField('store_ids', 'hidden', [
                'name'  => 'store_ids',
                'value' => $this->_storeManager->getStore()->getId()
            ]);
            $model->setStoreIds(0);
        } else {
            /** @var RendererInterface $rendererBlock */
            $rendererBlock = $this->getLayout()->createBlock(Element::class);
            $fieldset->addField('store_ids', 'multiselect', [
                'name'     => 'store_ids',
                'label'    => __('Store Views'),
                'title'    => __('Store Views'),
                'required' => true,
                'values'   => $this->systemStore->getStoreValuesForForm(false, true)
            ])->setRenderer($rendererBlock);
            if (!$model->hasData('store_ids')) {
                $model->setStoreIds(0);
            }
        }

        $fieldset->addField('customer_group_ids', 'multiselect', [
            'name'     => 'customer_group_ids[]',
            'label'    => __('Customer Groups'),
            'title'    => __('Customer Groups'),
            'required' => true,
            'values'   => $this->_customerCollection->toOptionArray(),
            'note'     => __('Select customer group(s) to display the promo banner to')
        ]);

        $fieldset->addField('category', 'select', [
            'name'   => 'category',
            'label'  => __('Promotion Category'),
            'title'  => __('Promotion Category'),
            'values' => $this->helperData->getCategoryList()
        ]);

        $fieldset->addField('from_date', 'date', [
            'name'        => 'from_date',
            'label'       => __('Start Date'),
            'title'       => __('Start Date'),
            'date_format' => 'M/d/yyyy',
            'timezone'    => false
        ]);

        $fieldset->addField('to_date', 'date', [
            'name'        => 'to_date',
            'label'       => __('End Date'),
            'title'       => __('End Date'),
            'date_format' => 'M/d/yyyy',
            'timezone'    => false
        ]);

        $fieldset->addField('priority', 'text', [
            'name'  => 'priority',
            'label' => __('Priority'),
            'class' => 'validate-digits',
            'note'  => __('Default is 0. The promo banner with the lower number will get the higher priority.')
        ]);

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

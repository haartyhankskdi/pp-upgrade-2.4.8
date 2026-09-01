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
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\BannerFactory;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModelRules;

/**
 * Class Condition
 *
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner\Edit\Tab
 */
class Condition extends Generic implements TabInterface
{
    /**
     * @var Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var Conditions
     */
    protected $conditions;

    /**
     * @var bannerFactory
     */
    protected $bannerFactory;

    /**
     * @var ResourceModelRules
     */
    protected $resourceModel;

    /**
     * Condition constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Fieldset $rendererFieldset
     * @param Conditions $conditions
     * @param BannerFactory $bannerFactory
     * @param ResourceModelRules $resourceModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Fieldset $rendererFieldset,
        Conditions $conditions,
        BannerFactory $bannerFactory,
        ResourceModelRules $resourceModel,
        array $data = []
    ) {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions       = $conditions;
        $this->bannerFactory    = $bannerFactory;
        $this->resourceModel    = $resourceModel;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare title for tab
     *
     * @return Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Prepare content for tab
     *
     * @return Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Conditions');
    }

    /**
     * Returns status flag about this tab can be show or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Tab class getter
     *
     * @return             string
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * Return URL link to Tab content
     *
     * @return             string
     * @codeCoverageIgnore
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return             bool
     * @codeCoverageIgnore
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('mppromobanner_banner');
        $form  = $this->addTabToForm($model);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param Banner $model
     * @param string $fieldsetId
     * @param string $formName
     *
     * @return Form
     * @throws LocalizedException
     */
    protected function addTabToForm($model, $fieldsetId = 'conditions_fieldset', $formName = 'mppromobanner_form')
    {
        $ruleId = $this->getRequest()->getParam('banner_id');
        if (!$model) {
            $model = $this->bannerFactory->create();
            $this->resourceModel->load($model, $ruleId);
        }
        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('mppromobanner_');

        $conditionsFieldSetId = $model->getConditionsFieldSetId($formName);
        $newChildUrl          = $this->getUrl(
            'mppromobanner/condition/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => $formName]
        );

        $renderer = $this->rendererFieldset->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId);

        $fieldset = $form->addFieldset(
            $fieldsetId,
            ['legend' => __('Apply the banner only if the following conditions are met (leave blank for all products).')]
        )->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name'           => 'conditions',
                'label'          => __('Conditions'),
                'title'          => __('Conditions'),
                'required'       => true,
                'data-form-part' => $formName
            ]
        )
            ->setRule($model)
            ->setRenderer($this->conditions);

        $form->setValues($model->getData());
        $this->setConditionFormName($model->getConditions(), $formName, $conditionsFieldSetId);

        return $form;
    }

    /**
     * @param AbstractCondition $conditions
     * @param string $formName
     * @param string $jsFormName
     *
     * @return void
     */
    private function setConditionFormName(AbstractCondition $conditions, $formName, $jsFormName)
    {
        $conditions->setFormName($formName);
        $conditions->setJsFormObject($jsFormName);

        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName, $jsFormName);
            }
        }
    }
}

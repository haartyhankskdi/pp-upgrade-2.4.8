<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Block\Adminhtml\User\Edit\Tab;

class Helpdesk extends \Magento\Backend\Block\Widget\Form implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    private $wysiwygConfig;
    /**
     * @var \Mirasvit\Helpdesk\Model\Config
     */
    private $config;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    private $formFactory;
    /**
     * @var \Mirasvit\Helpdesk\Model\UserFactory
     */
    private $userFactory;

    /**
     * Helpdesk constructor.
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param \Mirasvit\Helpdesk\Model\Config $config
     * @param \Mirasvit\Helpdesk\Model\UserFactory $userFactory
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Mirasvit\Helpdesk\Model\Config $config,
        \Mirasvit\Helpdesk\Model\UserFactory $userFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->wysiwygConfig = $wysiwygConfig;
        $this->config        = $config;
        $this->userFactory   = $userFactory;
        $this->formFactory   = $formFactory;
        $this->registry      = $registry;

        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Help Desk');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getAfter()
    {
        return 'roles_section';
    }

    /**
     * @return $this
     */
    public function _beforeToHtml()
    {
        $this->_initForm();

        return parent::_beforeToHtml();
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    protected function _initForm()
    {
        $form = $this->formFactory->create();
        $model = $this->registry->registry('permissions_user');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Help Desk Settings')]);

        $helpdeskUser = $this->userFactory->create();
        $resource = $helpdeskUser->getResource();
        $resource->load($helpdeskUser, $model->getId());
        if ($this->config->getGeneralIsWysiwyg()) {
            $fieldset->addField('signature', 'editor', [
                'label'   => __('Signature for Emails'),
                'name'    => 'signature',
                'value'   => $helpdeskUser->getSignature(),
                'config'  => $this->wysiwygConfig->getConfig(),
                'wysiwyg' => true,
                'style'   => 'height:20em',
            ]);
        } else {
            $fieldset->addField('signature', 'textarea', [
                'name'  => 'signature',
                'label' => __('Signature for Emails'),
                'id'    => 'signature',
                'value' => $helpdeskUser->getSignature(),
            ]);
        }

        $this->setForm($form);
    }
}

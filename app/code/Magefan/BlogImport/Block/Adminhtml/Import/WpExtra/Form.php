<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml\Import\WpExtra;

use Magento\Framework\Exception\LocalizedException;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magefan\Blog\Model\Config
     */
    private $config;

    /**
     * @var \Magefan\BlogImport\Model\Key
     */
    private $importKey;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magefan\Blog\Model\Config $config
     * @param \Magefan\BlogImport\Model\Key $importKey
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magefan\Blog\Model\Config $config,
        \Magefan\BlogImport\Model\Key $importKey,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->config = $config;
        $this->importKey = $importKey;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        //Magento\Backend\Model\Session
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
            ]]
        );
        $form->setUseContainer(true);

        $data = $this->_coreRegistry->registry('import_config')->getData();

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_authorization->isAllowed('Magefan_BlogImport::import')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }
        $isElementDisabled = false;

        $form->setHtmlIdPrefix('import_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => '']);

        $fieldset->addField(
            'notice',
            'label',
            [
                'label' => '',
                'name' => 'prefix',
                'after_element_html' =>
                    'Install <a href="https://wordpress.org/plugins/magefan-blog-export/" target="_blank">
                    Magefan Blog Export plugin</a> on your WordPerss site',
            ]
        );

        $fieldset->addField(
            'type',
            'hidden',
            [
                'name' => 'type',
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'import-key',
            'text',
            [
                'name' => 'import-key',
                'label' => __('Import Key'),
                'title' => __('Import Key'),
                'readonly' => true,
                'after_element_html' => '<br/><small>'
                    .'Here you can find the import key you can use when running import from WordPress. '
                    .'(Expires in 24 hours)</small>',
            ]
        );

        $data['import-key'] = $this->importKey->get();

        $form->setValues($data);

        $this->setForm($form);
        $this->_eventManager->dispatch('magefan_blog_import_wp_extra_prepare_form', ['form' => $form]);

        return parent::_prepareForm();
    }
}

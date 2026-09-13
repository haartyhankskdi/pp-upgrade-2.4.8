<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Block\Adminhtml\Import\Hubspot;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Hubspot import form block
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function _prepareForm()
    {

        //Magento\Backend\Model\Session
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
                ]
            ]
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
            'type',
            'hidden',
            [
                'name' => 'type',
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'next',
            'hidden',
            [
                'name' => 'next',
                'required' => true,
                'disabled' => $isElementDisabled,
            ]
        );

        $fieldset->addField(
            'file',
            'file',
            [
                'name' => 'file',
                'label' => __('CSV File'),
                'title' => __('CSV File'),
                'required' => true,
                'disabled' => $isElementDisabled,
                'after_element_html' => 'To get this CSV file '
                    . '<a href="https://knowledge.hubspot.com/website-and-landing-pages/export-web-content-and-data"'
                    .' target="_blank">click here</a>.',
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'store_id',
                'select',
                [
                    'name' => 'store_id',
                    'label' => __('Store View'),
                    'title' => __('Store View'),
                    'required' => true,
                    'values' => $this->_systemStore->getStoreValuesForForm(false, true),
                    'disabled' => $isElementDisabled,
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element::class
            );
            $field->setRenderer($renderer);
        } else {
            $fieldset->addField(
                'store_id',
                'hidden',
                ['name' => 'store_id', 'value' => $this->_storeManager->getStore(true)->getId()]
            );

            $data['store_id'] = $this->_storeManager->getStore(true)->getId();
        }

        $this->_eventManager->dispatch('magefan_blogimport_import_hubspot_prepare_form', ['form' => $form]);
        
        $data['type'] = 'hubspot';
        $data['next'] = 'blogimport/import/run';

        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}

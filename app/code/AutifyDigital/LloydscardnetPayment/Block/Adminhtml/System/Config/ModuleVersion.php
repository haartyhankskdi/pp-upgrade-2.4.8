<?php
/**
 * This software is the confidential and proprietary information of Autify Digital Ltd.
 * Unauthorized use, reproduction, or distribution of this software, in whole or in part, is strictly prohibited.
 * Copyright (c) 2020-Present Autify Digital Ltd.
 * This work is protected under applicable copyright and intellectual property laws.
 * All rights reserved. Distribution and disclosure are permitted only in accordance with
 * the terms of a valid written agreement with Autify Digital Ltd.
 */

namespace AutifyDigital\LloydscardnetPayment\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\ResourceInterface;

class ModuleVersion extends Field
{
    /**
     * Module name to retrieve version for
     */
    public const MODULE_NAME = 'AutifyDigital_LloydscardnetPayment';

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var ResourceInterface
     */
    protected $moduleResource;

    /**
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param ResourceInterface $moduleResource
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        ResourceInterface $moduleResource,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $version = $this->getModuleVersion();
        return '<strong>' . $version . '</strong>';
    }

    /**
     * Get module version from Magento's module system
     *
     * @return string
     */
    protected function getModuleVersion()
    {
        try {
            $module = $this->moduleList->getOne(self::MODULE_NAME);
            if ($module && isset($module['setup_version'])) {
                return $module['setup_version'];
            }
            
            // Fallback: Get data version from database (actual installed version)
            $dataVersion = $this->moduleResource->getDataVersion(self::MODULE_NAME);
            if ($dataVersion) {
                return $dataVersion;
            }
            
            // Another fallback: Get schema version from database
            $schemaVersion = $this->moduleResource->getDbVersion(self::MODULE_NAME);
            if ($schemaVersion) {
                return $schemaVersion;
            }
        } catch (\Exception $e) {
            // Return default version if reading fails
            return '3.0.15';
        }

        return '3.0.15';
    }
}

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
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Helper;

use Liquid\Template;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\GoogleTagManager\Model\TemplateFactory;
use Mageplaza\GoogleTagManager\Block\Adminhtml\LiquidFilters;

/**
 * Class Export
 * @package Mageplaza\GoogleTagManager\Helper
 */
class Export extends AbstractData
{
    public const CONFIG_MODULE_PATH = 'googletagmanager';
    public const PROFILE_FILE_PATH  = BP . '/pub/media/mageplaza/gtm/export';

    /**
     * @var LiquidFilters
     */
    protected $liquidFilters;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var TemplateFactory
     */
    protected $templateFactory;

    /**
     * Export constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param LiquidFilters $liquidFilters
     * @param TemplateFactory $templateFactory
     * @param File $file
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        LiquidFilters $liquidFilters,
        TemplateFactory $templateFactory,
        File $file
    ) {
        $this->liquidFilters   = $liquidFilters;
        $this->file            = $file;
        $this->templateFactory = $templateFactory;

        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param $code
     * @param null $store
     *
     * @return array|mixed
     */
    public function getConfigExport($code, $store = null)
    {
        if (!$store) {
            $store = $this->_getRequest()->getParam('store') ?: 0;
        }
        $code = ($code !== '') ? '/' . $code : '';

        return $this->getConfigValue(static::CONFIG_MODULE_PATH . '/export_gtm' . $code, $store);
    }

    /**
     * @return bool
     */
    public function checkInfo()
    {
        $adsConversionId = $this->getConfigExport('export_ads_conversion/conversion_id') ?: '';
        $adsConversion   = strlen($adsConversionId)
            && $this->getConfigExport('export_ads_conversion/conversion_label');

        $ga4            = $this->getConfigExport('export_ga4/measurement_id');
        $adsRemarketing = $this->getConfigExport('export_ads_remarketing/conversion_id');

        if (!$adsConversion && !$ga4 && !$adsRemarketing) {
            return false;
        }

        return true;
    }

    /**
     * Generate file content
     *
     * @return string
     */
    public function generateLiquidTemplate()
    {
        if (!$this->checkInfo()) {
            return false;
        }
        $config   = [
            'ads_conversion'  => [
                'conversionId'    => $this->getConfigExport('export_ads_conversion/conversion_id'),
                'conversionLabel' => $this->getConfigExport('export_ads_conversion/conversion_label')
            ],
            'ga4'             => [
                'measurementId' => $this->getConfigExport('export_ga4/measurement_id')
            ],
            'ads_remarketing' => [
                'conversionId'    => $this->getConfigExport('export_ads_remarketing/conversion_id'),
                'conversionLabel' => $this->getConfigExport('export_ads_remarketing/conversion_label') ?: ''
            ]
        ];
        $template = $this->prepareTemplate();

        return $template->render([
            'config' => $config
        ]);
    }

    /**
     * Prepare export template
     *
     * @return Template
     */
    public function prepareTemplate()
    {
        $template       = new Template;
        $filtersMethods = $this->liquidFilters->getFiltersMethods();
        $template->registerFilter($this->liquidFilters);

        $ga4             = $this->getConfigExport('export_ga4/measurement_id') ? true : false;
        $adsConversionId = $this->getConfigExport('export_ads_conversion/conversion_id') ?: '';
        $adsConversion   = strlen($adsConversionId) ? true : false;
        $adsRemarketing  = $this->getConfigExport('export_ads_remarketing/conversion_id') ? true : false;

        $exportTags    = $this->getExportTags($ga4, $adsConversion, $adsRemarketing);
        $exportTrigger = $this->getExportTrigger($ga4, $adsConversion, $adsRemarketing);
        $exportVar     = $this->getExportVariable($ga4, $adsConversion, $adsRemarketing);
        $buildInVar    = $this->getBuildInVariable();

        $templateHtml = '{
            "exportFormatVersion": 2,
            "containerVersion": {
                "tag": [' . $exportTags . '],
                "trigger": [' . $exportTrigger . '],
                "variable": [' . $exportVar . '],
                "builtInVariable": [' . $buildInVar . ']
            }
        }';
        $templateHtml = str_replace('}}', "| mpCorrect: '', ''}}", $templateHtml);
        array_push($filtersMethods, 'mpCorrect');

        $template->parse($templateHtml, $filtersMethods);

        return $template;
    }

    /**
     * @param $ga4
     * @param $adsConversion
     * @param $adsRemarketing
     *
     * @return string
     */
    protected function getExportTags($ga4, $adsConversion, $adsRemarketing)
    {
        $exportTags = [];

        if ($ga4) {
            $exportTags[] = $this->templateFactory->create()->load('ga4_tag', 'name')->getTemplate();
        }
        if ($adsConversion) {
            $exportTags[] = $this->templateFactory->create()->load('conversion_tag', 'name')->getTemplate();
        }
        if ($adsRemarketing) {
            $exportTags[] = $this->templateFactory->create()->load('remarketing_tag', 'name')->getTemplate();
        }
        if ($adsConversion || $adsRemarketing) {
            $exportTags[] = $this->templateFactory->create()->load('conversion_linker', 'name')->getTemplate();
        }
        $exportTags = implode(',', $exportTags);

        return $exportTags;
    }

    /**
     * @param $ga4
     * @param $adsConversion
     * @param $adsRemarketing
     *
     * @return string
     */
    protected function getExportTrigger($ga4, $adsConversion, $adsRemarketing)
    {
        $exportTrigger = [];

        if ($ga4) {
            $exportTrigger[] = $this->templateFactory->create()->load('ga4_trigger', 'name')->getTemplate();
        }
        if ($adsConversion || $ga4) {
            $exportTrigger[] = $this->templateFactory->create()->load('purchase_trigger', 'name')->getTemplate();
        }
        if ($adsRemarketing || $ga4) {
            $exportTrigger[] = $this->templateFactory->create()->load('window_loaded', 'name')->getTemplate();
        }

        $exportTrigger = implode(',', $exportTrigger);

        return $exportTrigger;
    }

    /**
     * @param $ga4
     * @param $adsConversion
     * @param $adsRemarketing
     *
     * @return string
     */
    protected function getExportVariable($ga4, $adsConversion, $adsRemarketing)
    {
        $exportVar = [];
        if ($ga4) {
            $exportVar[] = $this->templateFactory->create()->load('ga4_var', 'name')->getTemplate();
        }
        if ($adsConversion) {
            $exportVar[] = $this->templateFactory->create()->load('conversion_var', 'name')->getTemplate();
        }
        if ($adsRemarketing) {
            $exportVar[] = $this->templateFactory->create()->load('remarketing_var', 'name')->getTemplate();
        }

        $exportVar = implode(',', $exportVar);

        return $exportVar;
    }

    /**
     * @return mixed
     */
    protected function getBuildInVariable()
    {
        return $this->templateFactory->create()->load('build_in_var', 'name')->getTemplate();
    }

    /**
     * @param $fileName
     * @param $content
     *
     * @throws LocalizedException
     */
    public function createFile($fileName, $content)
    {
        $this->file->checkAndCreateFolder(self::PROFILE_FILE_PATH);
        $fileUrl = self::PROFILE_FILE_PATH . '/' . $fileName;
        $this->file->write($fileUrl, $content, 0777);
    }
}

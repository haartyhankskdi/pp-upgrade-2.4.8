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

namespace Mageplaza\GoogleTagManager\Controller\Adminhtml\Export;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\GoogleTagManager\Helper\Data;
use Mageplaza\GoogleTagManager\Helper\Export;

/**
 * Class Index
 * @package Mageplaza\GoogleTagManager\Controller\Adminhtml\Export
 */
class Index extends Action
{
    /**
     * @var Export
     */
    protected $exportHelper;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Export $exportHelper
     */
    public function __construct(
        Context $context,
        Export $exportHelper
    ) {
        $this->exportHelper = $exportHelper;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $result  = ['status' => false];
        $store   = $this->getRequest()->getParam('store');
        $website = $this->getRequest()->getParam('website');

        if ($website) {
            $isEnable = $this->exportHelper->getConfigValue('googletagmanager/general/enabled', $website, 'websites');
        } else {
            $isEnable = $this->exportHelper->getConfigGeneral('enabled', $store);
        }

        if (!$isEnable) {
            $result = [
                'status'  => false,
                'message' => __('Please enable module')
            ];
        } else {
            try {
                $content = $this->exportHelper->generateLiquidTemplate();
                if (!$content) {
                    $result = [
                        'status'  => false,
                        'message' => __('No information to export')
                    ];
                } else {
                    $content = str_replace('\{\{', '{{', $content);
                    $content = str_replace('\}\}', '}}', $content);

                    $this->exportHelper->createFile('mpgtm_export.json', $content);

                    $result = [
                        'status'    => true,
                        'file_name' => 'mpgtm_export.json'
                    ];
                }
            } catch (Exception $e) {
                $result['message'] = __('Something wrong when export file');
            }
        }

        $this->getResponse()->representJson(Data::jsonEncode($result));
    }
}

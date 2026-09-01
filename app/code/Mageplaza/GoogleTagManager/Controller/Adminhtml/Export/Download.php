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
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class Download
 * @package Mageplaza\GoogleTagManager\Controller\Adminhtml\Export
 */
class Download extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * Download constructor.
     *
     * @param FileFactory $fileFactory
     * @param Context $context
     */
    public function __construct(
        FileFactory $fileFactory,
        Context $context
    ) {
        $this->fileFactory = $fileFactory;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $fileName = 'mpgtm_export.json';
        try {
            return $this->fileFactory->create(
                $fileName,
                ['type' => 'filename', 'value' => 'mageplaza/gtm/export/' . $fileName],
                'media'
            );
        } catch (Exception $e) {
            $result['status'] = false;

            return $this->getResponse()->representJson(Data::jsonEncode($result));
        }
    }
}

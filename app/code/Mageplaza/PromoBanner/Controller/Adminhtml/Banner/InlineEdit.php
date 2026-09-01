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

namespace Mageplaza\PromoBanner\Controller\Adminhtml\Banner;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Mageplaza\PromoBanner\Model\Banner as BannerModel;
use Mageplaza\PromoBanner\Model\BannerFactory;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as ResourceModel;

/**
 * Class InlineEdit
 *
 * @package Mageplaza\PromoBanner\Controller\Adminhtml\Banner
 */
class InlineEdit extends Action
{
    /**
     * JSON Factory
     *
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var BannerFactory
     */
    protected $bannerFactory;

    /**
     * @var ResourceModel
     */
    protected $resourceModel;

    /**
     * InlineEdit constructor.
     *
     * @param JsonFactory $jsonFactory
     * @param BannerFactory $bannerFactory
     * @param ResourceModel $resourceModel
     * @param Context $context
     */
    public function __construct(
        JsonFactory $jsonFactory,
        BannerFactory $bannerFactory,
        ResourceModel $resourceModel,
        Context $context
    ) {
        $this->jsonFactory   = $jsonFactory;
        $this->bannerFactory = $bannerFactory;
        $this->resourceModel = $resourceModel;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error      = false;
        $messages   = [];
        $postItems  = $this->getRequest()->getParam('items', []);

        if (empty($postItems) || !$this->getRequest()->getParam('isAjax')) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error'    => true,
                ]
            );
        }

        foreach (array_keys($postItems) as $bannerId) {
            try {
                /** @var BannerModel $banner */
                $banner = $this->bannerFactory->create();
                $this->resourceModel->load($banner, $bannerId);
                if ($bannerId !== $banner->getId()) {
                    return $resultJson->setData(
                        [
                            'messages' => [__('The wrong banner is specified.')],
                            'error'    => true,
                        ]
                    );
                }
                $banner->setData(array_merge($banner->getData(), $postItems[$bannerId]));
                $this->resourceModel->save($banner);
            } catch (Exception $e) {
                $messages[] = $this->getErrorWithId(
                    $banner,
                    __($e->getMessage())
                );
                $error      = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error'    => $error
            ]
        );
    }

    /**
     * Add banner id to error message
     *
     * @param BannerModel $banner
     * @param string $errorText
     *
     * @return string
     */
    protected function getErrorWithId(BannerModel $banner, $errorText)
    {
        return '[Banner ID: ' . $banner->getId() . '] ' . $errorText;
    }
}

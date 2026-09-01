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
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Block\Adminhtml\Banner;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Newsletter\Model\Template;
use Magento\Newsletter\Model\TemplateFactory;

/**
 * Class Preview
 * @package Mageplaza\PromoBanner\Block\Adminhtml\Banner
 */
class Preview extends Widget
{
    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Block
     */
    protected $blockResource;

    /**
     * @var TemplateFactory
     */
    protected $_templateFactory;

    /**
     * Preview constructor.
     *
     * @param Context $context
     * @param BlockFactory $blockFactory
     * @param \Magento\Cms\Model\ResourceModel\Block $blockResource
     * @param TemplateFactory $templateFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        BlockFactory $blockFactory,
        \Magento\Cms\Model\ResourceModel\Block $blockResource,
        TemplateFactory $templateFactory,
        array $data = []
    ) {
        $this->blockFactory     = $blockFactory;
        $this->blockResource    = $blockResource;
        $this->_templateFactory = $templateFactory;

        parent::__construct($context, $data);
    }

    /**
     * @return Phrase|mixed|string
     * @throws Exception
     */
    protected function _toHtml()
    {
        return $this->getPreviewData();
    }

    /**
     * @return Phrase|mixed
     * @throws Exception
     */
    protected function getPreviewData()
    {
        $bannerParams = $this->getRequest()->getParam('mppromobanner_preview');

        if ($bannerParams['type'] === 'html') {
            $html = $this->getPreviewContent($bannerParams['content']);
        } else {
            $staticBlock = $this->getStaticBlock($bannerParams['cms']);
            if ($staticBlock) {
                $html = $this->getPreviewContent($staticBlock->getContent());
            } else {
                $html = __('Static block content not found');
            }
        }

        return $html;
    }

    /**
     * @param $content
     *
     * @return mixed
     * @throws Exception
     */
    public function getPreviewContent($content)
    {
        /** @var Template $template */
        $template = $this->_templateFactory->create();
        $template->setTemplateType(2);
        $template->setTemplateText($content);
        $templateProcessed = $this->_appState->emulateAreaCode(
            Template::DEFAULT_DESIGN_AREA,
            [$template, 'getProcessedTemplate']
        );

        return $templateProcessed;
    }

    /**
     * @param $identifier
     *
     * @return bool|Block
     */
    public function getStaticBlock($identifier)
    {
        try {
            $block = $this->blockFactory->create();
            $this->blockResource->load($block, $identifier);
            if (!$block->getId()) {
                throw new NoSuchEntityException(__('CMS Block with identifier "%1" does not exist.', $identifier));
            }

            return $block;
        } catch (Exception $e) {
            return false;
        }
    }
}

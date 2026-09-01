<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_UiChooserLayout
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\UiChooserLayout\Controller\Adminhtml\Chooser;

class Categories extends \Magento\Widget\Controller\Adminhtml\Widget\Instance
{
    /**
     * @var \Magento\Framework\View\Layout
     */
    protected $layout;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Layout $layout
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Layout $layout
    ) {
        $this->layout = $layout;
        parent::__construct($context, $coreRegistry, $widgetFactory, $logger, $mathRandom, $translateInline);
    }

    /**
     * Categories chooser Action (Ajax request)
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $result       = [];
        $selected     = $this->getRequest()->getParam('selected', '');
        $id           = $this->getRequest()->getParam('id');
        $type         = $this->getRequest()->getParam('type');
        $isAnchorOnly = $type == 'anchor_categories' ? true : false;
        /** @var \Magento\Widget\Block\Adminhtml\Widget\Catalog\Category\Chooser $chooser */
        $chooser = $this->layout->createBlock(\Magento\Widget\Block\Adminhtml\Widget\Catalog\Category\Chooser::class)
            ->setUseMassaction(true)
            ->setId($id)
            ->setIsAnchorOnly($isAnchorOnly)
            ->setSelectedCategories(explode(',', $selected));

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $html = str_replace("$('tree" . $id . "').fire('node:changed', {node:node})", "jQuery('#tree" . $id . "').trigger('node:changed', {node:node})", $chooser->toHtml());
        $result['html'] = $html;

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return;
    }
}
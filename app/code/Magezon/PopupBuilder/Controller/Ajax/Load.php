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
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Controller\Ajax;

class Load extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->registry      = $registry;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * New subscription action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $result['status'] = false;
        if ($this->getRequest()->isAjax()) {
            $handles = $this->getRequest()->getPost('handles');
            $ids = $this->getRequest()->getPost('ids');
            if ($handles) {
                $this->registry->register('current_handles', $handles);
                $block = $this->layoutFactory->create()->createBlock(\Magezon\PopupBuilder\Block\PopupList::class);
                $block->setIds($ids);
                $result['list'] = $block->getPopupIds();
                $result['status'] = true;
            }
        }
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return;
    }
}

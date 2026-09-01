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

namespace Magezon\PopupBuilder\Block\Popup;

class Preview extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magezon\Builder\Helper\Data
     */
    protected $builderHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry                      $registry
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
    }

    /**
     * @return null|\Magezon\PopupBuilder\Model\Popup
     */
    public function getCurrentPopup()
    {
        return $this->_coreRegistry->registry('popupbuilder_popup');
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $popup = $this->getCurrentPopup();
        $block = $this->getLayout()->createBlock(\Magezon\PopupBuilder\Block\Popup::class)
            ->setPopup($popup)->setPreviewMode(true);
        return $block->toHtml();
    }
}

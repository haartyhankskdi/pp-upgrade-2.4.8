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

namespace Magezon\UiChooserLayout\Model\Group;

use \Magento\Framework\App\ObjectManager;

class AbstractGroup extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @param \Magento\Backend\Model\UrlInterface     $backendUrl
     * @param \Magento\Framework\View\LayoutInterface $layout    
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->backendUrl = $backendUrl;
        $this->layout     = $layout;
    }

    /**
     * @return array
     */
    public function getHandles()
    {
        $coreRegistry = ObjectManager::getInstance()->get(\Magento\Framework\Registry::class);
        if ($coreRegistry->registry('current_handles')) {
            return $coreRegistry->registry('current_handles');
        }
    	return $this->layout->getUpdate()->getHandles();
    }
}
<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Core\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\DataObject;

abstract class AbstractMenu extends Template
{
    const SEPARATOR = 'separator';

    /**
     * @var array<int, \Magento\Framework\DataObject|string>
     */
    protected $items = [];

    /**
     * @var DataObject|null
     */
    protected $activeItem;

    /**
     * @var array
     */
    protected $visibleAt = [];

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);

        $this->context    = $context;
        $this->urlBuilder = $this->_urlBuilder;
    }

    /**
     * Build menu
     * @return $this
     */
    abstract protected function buildMenu();

    /**
     * Set menu visibility
     *
     * @param string|string[] $modules alias of modules (email, search, helpdesk)
     *
     * @return $this
     */
    protected function visibleAt($modules)
    {
        if (!is_array($modules)) {
            $modules = [$modules];
        }

        $this->visibleAt = $modules;

        return $this;
    }

    /**
     * @param bool $force
     *
     * @return $this
     */
    public function build($force = false)
    {
        if (!$this->isVisible() && !$force) {
            return parent::_prepareLayout();
        }

        $this->items = [];
        $this->buildMenu();

        $currentUrl = $this->urlBuilder->getCurrentUrl();

        foreach ($this->getFlatTree() as $item) {
            if (!is_object($item)) {
                continue;
            }

            if ($item->getData('url') == $currentUrl) {
                $this->activeItem = $item;
                break;
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * @return array<int, \Magento\Framework\DataObject|string>
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array       $data
     * @param string|null $parent
     *
     * @return $this
     */
    public function addItem(array $data, $parent = null)
    {
        $auth = $this->getAuthorization();

        if (!$auth->isAllowed($data['resource'])) {
            return $this;
        }

        if ($parent !== null) {
            foreach ($this->getFlatTree() as $item) {
                if (is_object($item) && $item->getData('id') == $parent) {
                    $items   = (array)$item->getData('items');
                    $items[] = new DataObject($data);

                    $item->setData('items', $items);
                    break;
                }
            }
        } else {
            $this->items[] = new DataObject($data);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addSeparator()
    {
        $this->items[] = self::SEPARATOR;

        return $this;
    }

    /**
     * @return string
     */
    public function getActiveTitle()
    {
        if ($this->activeItem) {
            return $this->activeItem->getData('title');
        } else {
            return $this->context->getPageConfig()->getTitle()->getShort();
        }
    }

    /**
     * @param array<int, \Magento\Framework\DataObject|string>|null $items
     *
     * @return \Generator<int, \Magento\Framework\DataObject|string>
     */
    protected function getFlatTree($items = null)
    {
        if (!$items) {
            $items = $this->items;
        }

        foreach ($items as $item) {
            yield $item;

            if (is_object($item) && $item->hasData('items')) {
                /** @var array<int, \Magento\Framework\DataObject|string> $subItems */
                $subItems = (array)$item->getData('items');
                foreach ($this->getFlatTree($subItems) as $subitem) {
                    yield $subitem;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        if (in_array($this->getRequest()->getModuleName(), $this->visibleAt)) {
            return true;
        }

        return false;
    }
}

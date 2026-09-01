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

namespace Magezon\PopupBuilder\Block;

class PopupList extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Magezon_PopupBuilder::list.phtml';

    /**
     * @var array
     */
    protected $_items;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context                  $context
     * @param \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $collectionFactory
     * @param array                                                             $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->timezone          = $context->getLocaleDate();
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return \Magezon\PopupBuilder\Model\ResourceModel\Popup\Collection
     */
    public function getItems()
    {
        if ($this->_items === null) {
            $items = [];
            $store = $this->_storeManager->getStore();
            $collection = $this->collectionFactory->create();
            $collection->addIsActiveFilter()->addStoreFilter($store);
            $now = $this->timezone->date()->format('Y-m-d');
            $collection->getSelect()->where(
                'from_date is null or from_date <= ?',
                $now
            )->where(
                'to_date is null or to_date >= ?',
                $now
            );
            $ids = ($this->getIds() && is_array($this->getIds())) ? $this->getIds() : [];
            if ($ids) {
                $collection->getSelect()->orWhere('main_table.popup_id IN (?)', $ids);
            }
            foreach ($collection as $popup) {
                if ($popup->isValid() || in_array($popup->getId(), $ids)) {
                    $items[] = $popup;
                }
            }
            $this->_items = $items;
        }
        return $this->_items;
    }

    /**
     * @return string
     */
    public function getPopupListHtml()
    {
        $html = '';
        $items = $this->getItems();
        foreach ($items as $popup) {
            $block = $this->getLayout()->createBlock(\Magezon\PopupBuilder\Block\Popup::class)->setPopup($popup);
            $html .= $block->toHtml();
        }
        return $html;
    }

    /**
     * @return string
     */
    public function getPopupIds()
    {
        $list = [];
        $items = $this->getItems();
        foreach ($items as $popup) {
            $block = $this->getLayout()->createBlock(\Magezon\PopupBuilder\Block\Popup::class)->setPopup($popup);
            $list[$popup->getId()] = $block->toHtml();
        }
        return $list;
    }
}

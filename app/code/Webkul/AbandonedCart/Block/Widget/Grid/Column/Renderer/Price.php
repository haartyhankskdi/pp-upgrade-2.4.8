<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\AbandonedCart\Block\Widget\Grid\Column\Renderer;

class Price extends \Magento\Customer\Block\Adminhtml\Edit\Tab\View\Grid\Renderer\Item
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $item
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $item)
    {
        return $this->escapeHtml($item->getQuoteCurrencyCode().' '.$item->getPrice());
    }
}

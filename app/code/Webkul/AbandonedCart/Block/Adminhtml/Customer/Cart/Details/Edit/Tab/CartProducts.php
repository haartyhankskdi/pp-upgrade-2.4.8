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

namespace Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit\Tab;

class CartProducts extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Quote\Model\Quote
     **/
    protected $_quote;

    /**
     * @var \Magento\Quote\Model\Quote\Item
     **/
    protected $_quoteItem;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data = []
     **/
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $quoteItem,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_quote = $quote;
        $this->_quoteItem = $quoteItem;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('abandonedcartproductgrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        $cartId = $this->getRequest()->getParam('cart_id');
        $quoteItem = $this->_quoteItem->getCollection()->addFieldToFilter('quote_id', $cartId)
        ->addFieldToFilter('base_price', ['neq'=>0]);
        $quoteItem->getSelect()
        ->join(
            $quoteItem->getTable('quote'),
            "main_table.quote_id = ".$quoteItem->getTable('quote').".entity_id"
        );
        // echo $quoteItem->getSelect();
        // echo"<pre>";print_r($quoteItem->getData());die("--==--");
        $this->setCollection($quoteItem);
        parent::_prepareCollection();
    }

    /**
     * prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'renderer'  => \Webkul\AbandonedCart\Block\Widget\Grid\Column\Renderer\Name::class,
                'index' =>  'Name'
            ]
        );
        $this->addColumn(
            'product_id',
            [
                'header' => __('Product Id'),
                'index' =>  'product_id'
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' =>  'sku'
            ]
        );
        $this->addColumn(
            'qty',
            [
                'header' => __('Quantity'),
                'index' =>  'qty'
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'renderer'  => \Webkul\AbandonedCart\Block\Widget\Grid\Column\Renderer\Price::class,
                'index' =>  'price'
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getRowUrl($row)
    {
        return "javascript:void(0)";
    }
}

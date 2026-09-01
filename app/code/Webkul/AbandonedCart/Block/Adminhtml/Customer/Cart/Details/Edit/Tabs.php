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
namespace Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('abandoned_cart_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Cart Information'));
    }

    /**
     * Prepare Layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $block = \Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit\Tab\CartDetails::class;
        $this->addTab(
            'gallery',
            [
                'label' => __('Cart Details'),
                'content' => $this->getLayout()
                                    ->createBlock($block)
                                    ->setTemplate('Webkul_AbandonedCart::cartDetails.phtml')
                                    ->toHtml(),
            ]
        );
        $block = \Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit\Tab\CartProducts::class;
        $this->addTab(
            'abandoned_cart_grid',
            [
                'label' => __('Cart Products'),
                'url' => $this->getUrl('*/*/cartdetails', ['_current' => true]),
                'class' => 'ajax'
            ]
        );
        $block = \Webkul\AbandonedCart\Block\Adminhtml\Customer\Cart\Details\Edit\Tab\SentEmail::class;
        $this->addTab(
            'sent_mails',
            [
                'label' => __('Sent Emails'),
                'content' => $this->getLayout()
                                    ->createBlock($block)
                                    ->setTemplate('Webkul_AbandonedCart::sentEmails.phtml')
                                    ->toHtml(),
                'class' => 'ajax'
            ]
        );
        return parent::_prepareLayout();
    }
}

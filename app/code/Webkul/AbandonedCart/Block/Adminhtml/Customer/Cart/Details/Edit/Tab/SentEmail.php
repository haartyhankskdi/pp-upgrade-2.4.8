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

class SentEmail extends \Magento\Backend\Block\Template
{
    /**
     * @var Webkul\AbandonedCart\Model\MailsLog
     **/
    protected $_abandonedCartMailHistory;

    /**
     * @var Webkul\AbandonedCart\Helper\Email
     **/
    protected $_mailHelper;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Webkul\AbandonedCart\Model\MailsLog $logMails
     * @param \Webkul\AbandonedCart\Helper\Email $abandonedCartMailHelper
     * @param array $data
     **/
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Webkul\AbandonedCart\Model\MailsLog $logMails,
        \Webkul\AbandonedCart\Helper\Email $abandonedCartMailHelper,
        array $data = []
    ) {
        $this->_abandonedCartMailHistory = $logMails;
        $this->_mailHelper = $abandonedCartMailHelper;
        parent::__construct($context, $data);
    }

    /**
     * get sent mails to the customer
     *
     * @return \Webkul\AbandonedCart\Model\MailsLog $data
     */
    public function getMailDetails()
    {
        $cartId = $this->getRequest()->getParam('cart_id');
        $data = $this->_abandonedCartMailHistory->getCollection()
                                                ->addFieldToFilter('quote_id', $cartId);
        return $data;
    }
}

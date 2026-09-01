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
namespace Webkul\AbandonedCart\Controller\Adminhtml\CustomEmail;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Send extends Action
{
    /**
     * @var \Webkul\AbandonedCart\Helper\Email
     **/
    protected $_mailHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quoteModel;

    /**
     * @var \Webkul\AbandonedCart\Logger\Logger
     */
    protected $_logger;

    /**
     * @param Context $context
     * @param \Webkul\AbandonedCart\Helper\Email $abandonedCartMailHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Quote\Model\Quote $quoteModel
     * @param \Webkul\AbandonedCart\Logger\Logger $logger
     **/
    public function __construct(
        Context $context,
        \Webkul\AbandonedCart\Helper\Email $abandonedCartMailHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Quote\Model\Quote $quoteModel,
        \Webkul\AbandonedCart\Logger\Logger $logger,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider
    ) {
        parent::__construct($context);
        $this->_mailHelper = $abandonedCartMailHelper;
        $this->_logger = $logger;
        $this->_localeDate = $localeDate;
        $this->_quoteModel = $quoteModel;
        $this->_filterProvider = $filterProvider;
    }

    /**
     * send mail to csutomer
     *
     * @return Magento\Framework\Controller\ResultFactory
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            if ($data['mailBody'] == "") {
                $message = __('Please check the details entered');
                $this->messageManager->addError($message);
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                return $resultRedirect;
            }
            $adminNameInMail = $data['adminName'];
            $adminEmailAddress = $data['adminEmail'];
            $cartId = $data['cartId'];
            $mailBody = $this->_filterProvider->getPageFilter()->filter($data['mailBody']);
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $this->_logger->info("Error in Sending Mail ".$e->getMessage());
            $this->messageManager->addError($message);
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
        $cartDetails = $this->_quoteModel->getCollection()
                                            ->addFieldToFilter('entity_id', $cartId);
        foreach ($cartDetails as $cart) {
            $recieverEmail = $cart->getCustomerEmail();
            $customerName = $cart->getCustomerFirstname();
        }
        $template = "abandoned_cart_custom_email";
        try {
            $this->_mailHelper->sendFollowMail(
                $adminNameInMail,
                $adminEmailAddress,
                $mailBody,
                $recieverEmail,
                $template,
                $customerName
            );
            $mailLogData = [
                'quote_id' => $cart->getEntityId(),
                'sent_by' => $adminEmailAddress,
                'sent_on' => $this->_localeDate->date()
                                                ->format('Y-m-d h:i:sa'),
                'mail_content' => $mailBody,
                'mode' => 2
            ];
            $this->_mailHelper->logSentMail($mailLogData);
            $this->messageManager->addSuccess(__('Mail Sent Successfully'));
        } catch (\Exception $e) {
            $this->_logger->info("Error in Sending Mail ".$e->getMessage());
            $this->messageManager->addError(__('Error Sending Mail'));
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}

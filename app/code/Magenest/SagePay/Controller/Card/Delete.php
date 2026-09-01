<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Card;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magenest\SagePay\Model\CardFactory;

class Delete extends Action
{
    protected $_cardFactory;
    protected $session;

    public function __construct(
        Context $context,
        CardFactory $cardFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->session = $customerSession;
        $this->_cardFactory = $cardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->session->isLoggedIn()) {
            return $this->_redirect('customer/account/login/');
        }
        $id = $this->getRequest()->getParam('id');

        try {
            /** @var \Magenest\SagePay\Model\Card $card */
            $card = $this->_cardFactory->create()->load($id);
            if ($card->isOwn($this->session->getCustomerId())) {
                $card->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the card.'));
            } else {
                $this->messageManager->addErrorMessage(__("Can't specify card"));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __("Error"));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('sagepay/customer/card');
    }
}

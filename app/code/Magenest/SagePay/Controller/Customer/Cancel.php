<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magenest\SagePay\Model\ProfileFactory;

class Cancel extends Action
{
    protected $_profileFactory;
    protected $session;

    /**
     * Cancel constructor.
     * @param Context $context
     * @param ProfileFactory $profileFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        ProfileFactory $profileFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->session = $customerSession;
        $this->_profileFactory = $profileFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        if (!$this->session->isLoggedIn()) {
            return $this->_redirect('customer/account/login/');
        }
        $profile_id = $this->getRequest()->getParam('profile_id');

        try {
            /** @var \Magenest\SagePay\Model\Profile $profile */
            $profile = $this->_profileFactory->create()->load($profile_id);
            if ($profile->isOwn($this->session->getCustomerId())) {
                $profile->cancelSubscription();
                $this->messageManager->addSuccessMessage(__('You cancelled the profile.'));
            } else {
                $this->messageManager->addErrorMessage(__("Can't specify subscription"));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __("Error"));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('sagepay/customer/profile');
    }
}

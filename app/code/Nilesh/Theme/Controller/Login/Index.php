<?php
namespace Nilesh\Theme\Controller\Login;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    protected $customerFactory;
    protected $accountManagement;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
        $this->accountManagement = $accountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        
        $result = $this->resultJsonFactory->create();

        try {
            $customer = $this->customerFactory->create()->loadByEmail($email);
            if ($customer && $this->accountManagement->authenticate($email, $password)) {
                $this->_getSession()->setCustomerAsLoggedIn($customer);
                return $result->setData(['success' => true, 'message' => 'Login successful']);
            } else {
                return $result->setData(['success' => false, 'message' => 'Invalid login credentials']);
            }
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Customer\Model\Session::class);
    }
}

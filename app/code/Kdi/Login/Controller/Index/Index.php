<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Login\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;

class Index extends Action
{
    protected $customerRepository;
    protected $accountManagement;
    protected $resultJsonFactory;
    protected $customerSession;

    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        JsonFactory $resultJsonFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        
        $result = $this->resultJsonFactory->create();

        try {
            $customer = $this->customerRepository->get($email);
            if ($customer && $this->accountManagement->authenticate($email, $password)) {
                $this->customerSession->setCustomerDataAsLoggedIn($customer);
                return $result->setData(['success' => true, 'message' => 'Login successful']);
            } else {
                return $result->setData(['success' => false, 'message' => 'Invalid login credentials']);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $result->setData(['success' => false, 'message' => 'Customer does not exist']);
        } catch (\Magento\Framework\Exception\AuthenticationException $e) {
            return $result->setData(['success' => false, 'message' => 'Invalid login credentials']);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}

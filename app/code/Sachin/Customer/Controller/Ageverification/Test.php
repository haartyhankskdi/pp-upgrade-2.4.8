<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Controller\Ageverification;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
class Test extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;
    protected $soapClientFactory;
    protected $customerSession;
    protected $customerRepository;
    protected $helperData;
    protected $ageFactory;
    protected $jsonHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Sachin\Customer\Helper\Ageverify $helperData,
        \Sachin\Customer\Model\AgeverificationFactory $ageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->soapClientFactory = $soapClientFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->helperData = $helperData;
        $this->ageFactory = $ageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
        $data = $this->getRequest()->getPost();
        
        //return $this->jsonResponse($data['firstname']);
        //exit();
        $customerid = $this->customerSession->getCustomer()->getId();
        //echo $customerid;exit();
        $customer = $this->customerRepository->getById($customerid);

        //$this->_customerSession->getCustomer()->setData('ageverification', $pharmacy_id); // set session
        //$ageFactory = $this->ageFactory->create();

        $client = $this->soapClientFactory->create($this->helperData->getGeneralConfig('soapurl'));        

        // Set up Classes ready for populating specific wsdl fields
        $params = new \stdClass;
        $params->Login = new \stdClass;
        $params->IDU = new \stdClass;
        $params->Person = new \stdClass;
        $params->Services = new \stdClass;

        // Enter the Username and password associated with your account
        // $params->Login->username                        = $this->helperData->getGeneralConfig('username');
        // $params->Login->password                        = $this->helperData->getGeneralConfig('password');
        $params->Login->username                        = '20018141';
        $params->Login->password                        = 'vBTGZni9KDNLM3SuXeXCnDAGZPuz';
        // $params->Login->username                        = '2089755';
        // $params->Login->password                        = '8zr5RVPAIDVFF6uh0YiArhwN6Wik';
        // $params->Login->username                        = '20018141';
        // $params->Login->password                        = 'vBTGZni9KDNLM3SuXeXCnDAGZPuz';

        // Reference is optional/mandatory based on user settings
        $params->IDU->Reference                         = '';
        // ID and IKey should be passed to continue a previous search
        $params->IDU->ID                                = '';
        $params->IDU->IKey                              = '';
        // $params->IDU->Scorecard                         = 'IDU Default';
        //$params->IDU->Scorecard                         = 'Identity Verification default';
        $params->IDU->Scorecard                         = 'Age Verification default';
        $params->IDU->equifaxUsername                   = '';
        $params->IDU->GlobalTransactionId                   = '';

        // Subject details
        $params->Person->forename                       = 'LINDA';
        $params->Person->middle                         = '';
        $params->Person->surname                        = 'ALBISON';
        $params->Person->gender                         = '2';
        $params->Person->dob                            = '11/09/1951';
        // $params->Person->dob                            = '';

        // Subject address details
        // $params->Person->address1                       = '15';
        // $params->Person->address2                       = '';
        // $params->Person->address3                       = '';
        $params->Person->address1                       = '11 STAFFORD GARDENS';
        $params->Person->address2                       = 'STAFFORD LANE, ST. HELIER, JERSEY';
        $params->Person->address3                       = '';
        $params->Person->address4                       = '';
        $params->Person->address5                       = '';
        $params->Person->address6                       = '';
        $params->Person->postcode                       = 'JE2 4NF';

        // Passport
        $params->Person->passport1                      = '';
        $params->Person->passport2                      = '';
        $params->Person->passport3                      = '';
        $params->Person->passport4                      = '';
        $params->Person->passport5                      = '';
        $params->Person->passport6                      = '';
        $params->Person->passport7                      = '';
        $params->Person->passport8                      = '';

        // Travel Visa
        $params->Person->travelvisa1                    = '';
        $params->Person->travelvisa2                    = '';
        $params->Person->travelvisa3                    = '';
        $params->Person->travelvisa4                    = '';
        $params->Person->travelvisa5                    = '';
        $params->Person->travelvisa6                    = '';
        $params->Person->travelvisa7                    = '';
        $params->Person->travelvisa8                    = '';
        $params->Person->travelvisa9                    = '';

        // ID Card
        $params->Person->idcard1                        = '';
        $params->Person->idcard2                        = '';
        $params->Person->idcard3                        = '';
        $params->Person->idcard4                        = '';
        $params->Person->idcard5                        = '';
        $params->Person->idcard6                        = '';
        $params->Person->idcard7                        = '';
        $params->Person->idcard8                        = '';
        $params->Person->idcard9                        = '';
        $params->Person->idcard10                       = '';

        // Driving Licence
        $params->Person->drivinglicence1                = '';
        $params->Person->drivinglicence2                = '';
        $params->Person->drivinglicence3                = '';

        // Card Number
        $params->Person->cardnumber                     = '';
        $params->Person->cardtype                       = '';

        // NI
        $params->Person->ni                             = '';

        // NHS
        $params->Person->nhs                            = '';

        // Birth Details
        $params->Person->bforename                      = '';
        $params->Person->bmiddle                        = '';
        $params->Person->bsurname                       = '';
        $params->Person->maiden                         = '';
        $params->Person->bdistrict                      = '';
        $params->Person->bcertificate                   = '';

        // Electricity Bill
        $params->Person->mpannumber1                    = '';
        $params->Person->mpannumber2                    = '';
        $params->Person->mpannumber3                    = '';
        $params->Person->mpannumber4                    = '';

        // Bank Account
        $params->Person->sortcode                       = '';
        $params->Person->accountnumber                  = '';

        // Marriage Details
        $params->Person->msubjectforename               = '';
        $params->Person->msubjectsurname                = '';
        $params->Person->mpartnerforename               = '';
        $params->Person->mpartnersurname                = '';
        $params->Person->mdate                          = '';
        $params->Person->mdistrict                      = '';
        $params->Person->mcertificate                   = '';

        // Poll Number Details
        $params->Person->pollnumber                     = '';

        // Email Details
        $params->Person->email                          = '';
        $params->Person->email2                         = '';

        // Document Authentication Details
        $params->Person->docfront                       = '';
        $params->Person->docback                        = '';
        $params->Person->docsize                        = '';

        // Legacy One Time Password Fields
        $params->Person->landline1                      = '';
        $params->Person->landline2                      = '';
        $params->Person->mobile1                        = '';
        $params->Person->mobile2                        = '';

        $params->Person->otplandline1                   = '';
        $params->Person->otplandline2                   = '';
        $params->Person->otpmobile1                     = '';
        $params->Person->otpmobile2                     = '';

        // Mobile App Registration
        $params->Person->mobileappreg                   = '';
        $params->Person->uklexid                   = '';
        $params->Person->uklexidasofdate                   = '';

        // Enable minimum services for the IDU configuration
        $params->Services->address                      = 1;
        $params->Services->deathscreen                  = 0;
        $params->Services->dob                          = 1;
        $params->Services->sanction                     = 0;
        $params->Services->insolvency                   = 0;
        $params->Services->ccj                          = 0;

        // If your organisation has access to Crediva then this
        // should be added to the code:
        $params->Services->crediva                      = 0;

        // Explicitly disable non required services
        $params->Services->passport                     = 0;
        $params->Services->driving                      = 0;
        $params->Services->birth                        = 0;
        $params->Services->smartlink                    = 0;
        $params->Services->ni                           = 0;
        $params->Services->nhs                          = 0;
        $params->Services->cardnumber                   = 0;
        $params->Services->mpan                         = 0;
        $params->Services->bankaccountvalidation        = 0;
        $params->Services->creditactive                 = 0;
        $params->Services->travelvisa                   = 0;
        $params->Services->idcard                       = 0;
        $params->Services->bankaccountverification      = 0;
        $params->Services->companydirector              = 0;
        $params->Services->searchactivity               = 0;
        $params->Services->prs                          = 0;
        $params->Services->marriage                     = 0;
        $params->Services->pollnumber                   = 0;
        $params->Services->onlineprofile                = 0;
        $params->Services->age                          = 1;
        $params->Services->docauth                      = 0;
        $params->Services->onetimepassword              = 0;
        $params->Services->emailaddresses               = 0;
        $params->Services->phonenumbers                 = 0;
        $params->Services->kba                          = 0;
        $params->Services->emailrisk                    = 0;
        $params->Services->mobileappreg                 = 0;
        $params->Services->threatmetrix                 = 0;
        $params->Services->nfiaddress                   = 0;

        // var_dump($params);
        // $results = $client->GlobalTransactionId = '1';
        $results = $client->IDUProcess($params);

        $ageverify = $results->Summary->ResultText;
        $electroll = $results->Address->Source;
        //$tracesmart_register = array();
        $tracesmart_register = $results->Address->Source;
        $telephone = $results->Address->Telephone;
        $dob = $results->Address->DOBAppended;
        // echo $tracesmart_register;
        // echo "</br>".$ageverify;
        // echo "</br>".$electroll;
        // echo "</br>".$telephone;
        // echo "</br>".$dob;
        // exit();
        if($ageverify == "PASS"){
            $customer->setCustomAttribute('age_verification', 1);   
            //$this->customerRepository->save($customer);
            //$this->helperData->saveCustomerAgeverify($data);
            //$age = $results->Age->AgeLower;
        }
        
        if (strpos($tracesmart_register, 'TR') !== false)
          {
            $customer->setCustomAttribute('tracesmart_register', 1);
          
          }
          if ((!empty($tracesmart_register)))
          {
            $customer->setCustomAttribute('electroll_role', 1);
          
          }
          if($telephone == "XD"){
            $customer->setCustomAttribute('tracesmart_telephone', 1);
            
          }
          if($dob == "1"){
            $customer->setCustomAttribute('tracesmart_dob', 1);
          }
          $this->customerRepository->save($customer);
            echo "success";exit();
            return $this->jsonResponse($age);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}


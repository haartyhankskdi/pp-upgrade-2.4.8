<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sachin\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Registerverify extends AbstractHelper
{

	protected $soapClientFactory;
	protected $countryFactory;
    protected $customerRepository;

	const XML_PATH_AGEVERIFICATION = 'ageverification/';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
    	$this->soapClientFactory = $soapClientFactory;
    	$this->countryFactory = $countryFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }
    
    public function getGeneralConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::XML_PATH_AGEVERIFICATION .'credential/'. $code, $storeId);
    }

    public function getCountryname($countryCode){    
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    public function getTracesmartDob($customerid){
        $customer = $this->customerRepository->getById($customerid);
        if($customer->getCustomAttribute('tracesmart_dob')){
        $tracesmart_dob = $customer->getCustomAttribute('tracesmart_dob')->getValue();
        return $tracesmart_dob;
        }
    }

    public function getAgeverification($customerid){
        $customer = $this->customerRepository->getById($customerid);
        if($customer->getCustomAttribute('age_verification')){
        $age_verification = $customer->getCustomAttribute('age_verification')->getValue();
        return $age_verification;
        }
    }

    public function getElectroll($customerid){
        $customer = $this->customerRepository->getById($customerid);
        if($customer->getCustomAttribute('electroll_role')){
        $electroll_role = $customer->getCustomAttribute('electroll_role')->getValue();
        return $electroll_role;
        }
    }

    public function getTracesmartRegister($customerid){
        $customer = $this->customerRepository->getById($customerid);
        if($customer->getCustomAttribute('tracesmart_register')){
        $tracesmart_register = $customer->getCustomAttribute('tracesmart_register')->getValue();
        return $tracesmart_register;
        }
    }

    public function getTracesmartTelephone($customerid){
        $customer = $this->customerRepository->getById($customerid);
        if($customer->getCustomAttribute('tracesmart_telephone')){
        $tracesmart_telephone = $customer->getCustomAttribute('tracesmart_telephone')->getValue();
        return $tracesmart_telephone;
        }
    }
    // public function getCustAttribute($customerid){
    //     $customer = $this->customerRepository->getById($customerid);
    //     $tracesmart_dob = $customer->getCustomAttribute('tracesmart_dob')->getValue();
    //     $age_verification = $customer->getCustomAttribute('age_verification')->getValue();
    //     $electroll_role = $customer->getCustomAttribute('electroll_role')->getValue();
    //     $tracesmart_register = $customer->getCustomAttribute('tracesmart_register')->getValue();
    //     $tracesmart_telephone = $customer->getCustomAttribute('tracesmart_telephone')->getValue();
    //     $custAttr = [$tracesmart_dob,$age_verification,$electroll_role,$tracesmart_register,$tracesmart_telephone];
    //     return $custAttr;
    // }
    public function VerifyAge($customerid,$firstname,$lastname,$dob,$gender,$street1,$street2,$city,$country_id,$postcode)
    {
    	$client = $this->soapClientFactory->create($this->getGeneralConfig('soapurl'));        
        //$customerid = $this->customerSession->getCustomer()->getId();
        //echo $customerid;exit();
        $customer = $this->customerRepository->getById($customerid);
        // Set up Classes ready for populating specific wsdl fields
        $params = new \stdClass;
        $params->Login = new \stdClass;
        $params->IDU = new \stdClass;
        $params->Person = new \stdClass;
        $params->Services = new \stdClass;

        // Enter the Username and password associated with your account
        $params->Login->username                        = $this->getGeneralConfig('username');
        $params->Login->password                        = $this->getGeneralConfig('password');
        // $params->Login->username                        = '20018141';
        // $params->Login->password                        = 'vBTGZni9KDNLM3SuXeXCnDAGZPuz';
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
        $params->IDU->Scorecard                         = 'Age Verification default';
        $params->IDU->equifaxUsername                   = '';
        $params->IDU->GlobalTransactionId                   = '';

        // Subject details
        $params->Person->forename                       = $firstname;
        $params->Person->middle                         = '';
        $params->Person->surname                        = $lastname;
        $params->Person->gender                         = $gender;
        $params->Person->dob                            = $dob;
        // $params->Person->dob                            = '';

        // Subject address details
        // $params->Person->address1                       = '15';
        // $params->Person->address2                       = '';
        // $params->Person->address3                       = '';
        $params->Person->address1                       = $street1;
        $params->Person->address2                       = $street2;
        $params->Person->address3                       = '';
        $params->Person->address4                       = '';
        $params->Person->address5                       = '';
        $params->Person->address6                       = '';
        $params->Person->postcode                       = $postcode;

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
        //$dob = $results->Address->DOBAppended;
        $dob = $results->DOB->TracesmartDOB;
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
            //echo "success";exit();
    }

    public function AdminVerifyAge($customerid,$firstname,$lastname,$dob,$gender,$street1,$city,$postcode)
    {
        $client = $this->soapClientFactory->create($this->getGeneralConfig('soapurl'));        
        //$customerid = $this->customerSession->getCustomer()->getId();
        //echo $customerid;exit();
        $customer = $this->customerRepository->getById($customerid);
        // Set up Classes ready for populating specific wsdl fields
        $params = new \stdClass;
        $params->Login = new \stdClass;
        $params->IDU = new \stdClass;
        $params->Person = new \stdClass;
        $params->Services = new \stdClass;

        // Enter the Username and password associated with your account
        $params->Login->username                        = $this->getGeneralConfig('username');
        $params->Login->password                        = $this->getGeneralConfig('password');
        // $params->Login->username                        = '20018141';
        // $params->Login->password                        = 'vBTGZni9KDNLM3SuXeXCnDAGZPuz';
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
        $params->IDU->Scorecard                         = 'Age Verification default';
        $params->IDU->equifaxUsername                   = '';
        $params->IDU->GlobalTransactionId                   = '';

        // Subject details
        $params->Person->forename                       = $firstname;
        $params->Person->middle                         = '';
        $params->Person->surname                        = $lastname;
        $params->Person->gender                         = $gender;
        $params->Person->dob                            = $dob;
        // $params->Person->dob                            = '';

        // Subject address details
        // $params->Person->address1                       = '15';
        // $params->Person->address2                       = '';
        // $params->Person->address3                       = '';
        $params->Person->address1                       = $street1;
        $params->Person->address2                       = '';
        $params->Person->address3                       = '';
        $params->Person->address4                       = '';
        $params->Person->address5                       = '';
        $params->Person->address6                       = '';
        $params->Person->postcode                       = $postcode;

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
        //$dob = $results->Address->DOBAppended;
        $dob = $results->DOB->TracesmartDOB;
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
        }else{
            $customer->setCustomAttribute('age_verification', 0);
        }
        
        if (strpos($tracesmart_register, 'TR') !== false)
          {
            $customer->setCustomAttribute('tracesmart_register', 1);
          
          }else{
            $customer->setCustomAttribute('tracesmart_register', 0);
          }
          if ((!empty($tracesmart_register)))
          {
            $customer->setCustomAttribute('electroll_role', 1);
          
          }else{
           $customer->setCustomAttribute('electroll_role', 0);  
          }
          if($telephone == "XD"){
            $customer->setCustomAttribute('tracesmart_telephone', 1);
            
          }else{
            $customer->setCustomAttribute('tracesmart_telephone', 0);
          }
          if($dob == "1"){
            $customer->setCustomAttribute('tracesmart_dob', 1);
          }else{
            $customer->setCustomAttribute('tracesmart_dob', 0);
          }
          $this->customerRepository->save($customer);
            //echo "success";exit();
    }

}

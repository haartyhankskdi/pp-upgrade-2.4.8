<?php

/**
 * @auther Satyam Kumar
 * Email satyam@haartyhanks.com
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Haartyhanks\LNAPI\Controller\Adminhtml\Remote;

use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\ManagerInterface;
use Haartyhanks\LNAPI\Model\EntityFactory;
use Haartyhanks\LNAPI\Helper\Soap;
use Haartyhanks\LNAPI\Model\Entity;
use Magento\Framework\Mail\Template\TransportBuilder;
use Haartyhanks\LNAPI\Helper\System;


class Mail extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;

    protected $addressFactory;

    protected $messageManager;

    protected $resultRedirectFactory;

    protected $_formKey;

    protected $_customer;

    protected $_entityFactory;

    protected $_soap;

    protected $_transportBuilder;

    protected $system;

    protected $logger;
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        AddressFactory $addressFactory,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        FormKey $formKey,
        Customer $customer,
        EntityFactory $_entityFactory,
        Soap $_soap,
        TransportBuilder $_transportBuilder,
        System $system,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->addressFactory = $addressFactory;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->_formKey = $formKey;
        $this->_customer = $customer;
        $this->_entityFactory = $_entityFactory;
        $this->_soap = $_soap;
        $this->_transportBuilder = $_transportBuilder;
        $this->system = $system;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $params = $this->getRequest()->getParams();


        
        $isMailSend = $this->sendMail();
        
        if ($isMailSend) {
            $this->messageManager->addSuccessMessage(__('Mail was sent successfully.'));
            $resultRedirect->setPath(
                'customer/index/edit',
                [
                    'id' => $params['customer_id'],
                    'form_key' => $this->getFormKey(),
                    'active_tab' => 'customer_edit_tab_lnverification'
                ]
            );
            return $resultRedirect;
        } {

            $this->messageManager->addErrorMessage(__('Mail could not be sent.'));
            $resultRedirect->setPath(
                'customer/index/edit',
                [
                    'id' => $params['customer_id'],
                    'form_key' => $this->getFormKey(),
                    'active_tab' => 'customer_edit_tab_lnverification'
                ]
            );
            return $resultRedirect;
        }
    }

    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }



    /**
     * Get customer first name, last name, and date of birth by customer ID
     *
     * @param int $customerId
     * @return array|null
     */
    public function getCustomerNameAndDobById($customerId)
    {
        if (!$customerId) {
            return null;
        }

        try {
            $customer = $this->_customer->load($customerId);
            if (!$customer || !$customer->getId()) {
                return null;
            }

            $firstName = $customer->getFirstname();
            $lastName = $customer->getLastname();
            $dob = $customer->getDob();
            $customerEmail = $customer->getEmail();

            return [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'dob' => $dob,
                'mail' => $customerEmail
            ];
        } catch (\Exception $e) {
            // Optionally log the exception
            return null;
        }
    }

    public function preparePayload($id, $ikey, $journeyId, $customerFirstname, $customerLastname, $customerDob, $customerEmail)
    {
        // SOAP Request XML payload
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:idu" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
<SOAP-ENV:Body>
  <ns1:IDUProcess>
    <params xsi:type="ns1:Request">
      <IDU xsi:type="ns1:IDUDetails">
        <Reference xsi:nil="true"/>
        <ID xsi:nil="true"/>
        <IKey xsi:nil="true"/>
        <Scorecard xsi:nil="true"/>
        <equifaxUsername xsi:nil="true"/>
      </IDU>
      <Login xsi:type="ns1:LoginDetails">
        <username xsi:type="xsd:string">{$id}</username>
        <password xsi:type="xsd:string">{$ikey}</password>
      </Login>
      <Person xsi:type="ns1:PersonDetails">
          <forename xsi:type="xsd:string">{$customerFirstname}</forename>
          <surname xsi:type="xsd:string">{$customerLastname}</surname>
          <dob xsi:type="xsd:string">{$customerDob}</dob>
          <mobile1 xsi:type="xsd:string"></mobile1>
          <email xsi:type="xsd:string">{$customerEmail}</email>
          <remotecheck xsi:type="tns:RemoteCheckRequest">
              <JourneyID xsi:type="xsd:string">{$journeyId}</JourneyID>
          </remotecheck>
      </Person>
      <Services xsi:type="ns1:ServiceDetails">
        <remotecheck>true</remotecheck>
      </Services>
    </params>
  </ns1:IDUProcess>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
XML;

        return $xmlPayload;
    }



    /**
     * Loads customer by custom attribute (customer_id) using entityFactory.
     * Returns true if the customer exists, otherwise false.
     * 
     * @param string|int $customerId
     * @return bool
     */
    public function isCustomerIdExists($customerId)
    {
        if (!$customerId) {
            return false;
        }
        try {
            $customerEntity = $this->_entityFactory->create();
            $customerEntity->load($customerId, 'customer_id'); // entity_id is the default PK, change if your attribute code is different
            if ($customerEntity && $customerEntity->getId()) {
                return true;
            }
        } catch (\Exception $e) {
            // Optionally log exception
        }
        return false;
    }


    public function getInviteLink($xmlPayload)
    {
        $response = $this->_soap->sendSoapRequest($xmlPayload);

        // Parse the response to extract required details
        $soapResponse = new \SimpleXMLElement($response);

        // Extract required data (adjust XPath if necessary)
        $inviteLink = (string)$soapResponse->xpath('//InviteLink')[0];
        $inviteExpiresAt = (string)$soapResponse->xpath('//InviteExpiresAt')[0];
        $applicationStatus = (string)$soapResponse->xpath('//ApplicationStatus')[0];
        $brandName = (string)$soapResponse->xpath('//BrandName')[0];
        $id = (string)$soapResponse->xpath('//ID')[0];
        $IKey = (string)$soapResponse->xpath('//IKey')[0];
        $ProfileURL = (string)$soapResponse->xpath('//ProfileURL')[0];

        try {
            return [
                'inviteLink' => $inviteLink,
                'inviteExpiresAt' => $inviteExpiresAt,
                'applicationStatus' => $applicationStatus,
                'brandName' => $brandName,
                'id' => $id,
                'IKey' => $IKey,
                'ProfileURL' => $ProfileURL
            ];
        } catch (\Exception $e) {
            // Optionally log the exception or handle it as needed
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }




    /**
     * Send LexisNexis invite email to customer.
     *
     * @return bool
     */
    public function sendMail(): bool
    {
        try {
            $params = (array) $this->getRequest()->getParams();

            if (empty($params['customer_id'])) {
                $this->logger->error('LNAPI: Missing customer_id in request params.');
                return false;
            }

            $customerId = (int) $params['customer_id'];
            if ($customerId <= 0) {
                $this->logger->error('LNAPI: Invalid customer_id received.', ['customer_id' => $params['customer_id']]);
                return false;
            }

            $customerData = $this->getCustomerNameAndDobById($customerId);

            if (empty($customerData['mail'])) {
                $this->logger->error('LNAPI: Customer email not found.', ['customer_id' => $customerId]);
                return false;
            }

            // Config values
            $id        = '2331654'; //(string) $this->system->getConfigValue(System::ID);
            //$encrypted = (string) $this->system->getConfigValue(System::IKEY);
            $ikey      = 'XUrLN0WyOw8cHm3H7T1Xj4Dm7mzx'; //(string) $this->system->decryptValue($encrypted);
            $journeyId =  '1711'; //(string) $this->system->getConfigValue(System::JOURNEY_ID);

            // Prepare API Payload
            $xmlPayload = $this->preparePayload(
                $id,
                $ikey,
                $journeyId,
                (string) $customerData['firstname'],
                (string) $customerData['lastname'],
                (string) $customerData['dob'],
                (string) $customerData['mail']
            );

            $inviteLinkData = $this->getInviteLink($xmlPayload);


            // Email template vars
            $emailVars = [
                'customer_name'       => trim($customerData['firstname'] . ' ' . $customerData['lastname']),
                'invite_link'         => (string) ($inviteLinkData['inviteLink'] ?? ''),
                'invite_expires_at'   => (string) ($inviteLinkData['inviteExpiresAt'] ?? ''),
                'application_status'  => (string) ($inviteLinkData['applicationStatus'] ?? ''),
                'brand_name'          => (string) ($inviteLinkData['brandName'] ?? ''),
            ];

            $setData =  [
                'customer_name'       => trim($customerData['firstname'] . ' ' . $customerData['lastname']),
                'verification_link'         => (string) ($inviteLinkData['inviteLink'] ?? ''),
                'used_verification_link' => (string) ($inviteLinkData['inviteLink'] ?? ''),
                'current_link_expiry_at'   => (string) ($inviteLinkData['inviteExpiresAt'] ?? ''),
                'application_status'  => (string) ($inviteLinkData['applicationStatus'] ?? ''),
                'brand_name'          => (string) ($inviteLinkData['brandName'] ?? ''),
                'remote_check_id'          => (string) ($inviteLinkData['id'] ?? ''),
                'remote_check_key'          => (string) ($inviteLinkData['IKey'] ?? ''),
                'remote_check_profile_url'          => (string) ($inviteLinkData['ProfileURL'] ?? ''),
                'attempts' => 0,
                'link_generated_count' => 0,
                'reminder_sent_count' =>0,
                'status' => 'PENDING'
            ];

            $this->setModelData($setData);



            $storeId = 0; //(int) $this->storeManager->getStore()->getId();

            // If you want to keep template ID = 34 fixed, keep it here.
            // Better approach: store config value (recommended).
            $templateId = 34;

            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($emailVars)
                ->setFromByScope('general', $storeId)
                ->addTo($customerData['mail'])
                ->getTransport();

            $transport->sendMessage();

            return true;
        } catch (\Throwable $e) {
            $this->logger->critical('LNAPI: Error while sending invite email.', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function setModelData(array $data = [])
    {
        $params = $this->getRequest()->getParams();

        // Load entity by customer_id
        $entity = $this->_entityFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $params['customer_id'])
            ->getFirstItem();

        if ($entity && $entity->getId()) {
            // If found, update with provided data
            foreach ($data as $key => $value) {
                $entity->setData($key, $value);
            }
        } else {
            // If not found, create new with customer_id and provided data
            $entity = $this->_entityFactory->create();
            $entity->setData('customer_id', $params['customer_id']);
            foreach ($data as $key => $value) {
                $entity->setData($key, $value);
            }
        }


        $entity->save();

        return $entity;
    }
}

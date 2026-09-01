<?php

namespace Haartyhanks\LNAPI\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Haartyhanks\LNAPI\Helper\Soap;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Haartyhanks\LNAPI\Model\Entity as ModelEntity;
use Haartyhanks\LNAPI\Helper\System;


class SendSoapRequest implements ObserverInterface
{
    protected $orderRepository;
    protected $soapHelper;
    protected $transportBuilder;
    protected $storeManager;
    protected $modelEntity;
    protected $system;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Soap $soapHelper,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ModelEntity $modelEntity,
        System $system
    ) {
        $this->orderRepository = $orderRepository;
        $this->soapHelper = $soapHelper;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->modelEntity = $modelEntity;
        $this->system = $system;
    }

    public function execute(Observer $observer)
    {
      if($this->system->getConfigValue(System::STATUS))
      {
        try {
          // Get the order object
          $order = $observer->getEvent()->getOrder();

          $storeCode = $order->getStore()->getCode();
          
          $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/lnapi_observer.log');
          $logger = new \Zend_Log();
          $logger->addWriter($writer);
          $logger->info('store code ' . print_r($storeCode, true));
          

          $id = $this->system->getConfigValue(System::ID);
          $Dikey = $this->system->getConfigValue(System::IKEY);
          $ikey = $this->system->decryptValue($Dikey);
          $journeyId = $this->system->getConfigValue(System::JOURNEY_ID);

          // Fetch customer details
          $customerFirstname = $order->getCustomerFirstname();
          $customerLastname = $order->getCustomerLastname();
          $customerDob = $order->getCustomerDob(); // Ensure DOB is set in customer profile
          $customerEmail = $order->getCustomerEmail();

          if (!$customerFirstname || !$customerLastname || !$customerEmail) {
              throw new \Exception('Customer details are incomplete.');
          }

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

          // Send SOAP request using the helper
          $response = $this->soapHelper->sendSoapRequest($xmlPayload);

          // Parse the response to extract required details
          $soapResponse = new \SimpleXMLElement($response);

          $customerId = $order->getCustomerId();
          $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/soap.log');
          $zendLogger = new \Zend_Log();
          $zendLogger->addWriter($writer);
          $zendLogger->info(" Customer Id " . print_r($soapResponse, true));

          
          // Extract required data (adjust XPath if necessary)
          $inviteLink = (string)$soapResponse->xpath('//InviteLink')[0];
          $inviteExpiresAt = (string)$soapResponse->xpath('//InviteExpiresAt')[0];
          $applicationStatus = (string)$soapResponse->xpath('//ApplicationStatus')[0];
          $brandName = (string)$soapResponse->xpath('//BrandName')[0];
          $id = (string)$soapResponse->xpath('//ID')[0];
          $IKey = (string)$soapResponse->xpath('//IKey')[0];
          $ProfileURL = (string)$soapResponse->xpath('//ProfileURL')[0];


          // Prepare email data
          $emailData = [
              'customer_name' => $customerFirstname . ' ' . $customerLastname,
              'invite_link' => $inviteLink,
              'invite_expires_at' => $inviteExpiresAt,
              'application_status' => $applicationStatus,
              'brand_name' => $brandName,
          ];

          $customerId = $order->getCustomerId();
          $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/soap.log');
          $zendLogger = new \Zend_Log();
          $zendLogger->addWriter($writer);
          $zendLogger->info(" Customer Id " . print_r($customerId, true));


          // Send the email
          $this->sendEmail($customerEmail, $emailData);
          $this->modelEntity->setData('customer_id' , $order->getCustomerId());
          $this->modelEntity->setData('verification_link', $inviteLink );
          $this->modelEntity->setData('used_verification_link', $inviteLink);
          $this->modelEntity->setData('current_link_expiry_at',$inviteExpiresAt);
          $this->modelEntity->setData('remote_check_id',$id);
          $this->modelEntity->setData('remote_check_key',$IKey);
          $this->modelEntity->setData('remote_check_profile_url',$ProfileURL);
          $this->modelEntity->setData('status', $applicationStatus);
          $this->modelEntity->setData('is_verified', 0);
          $this->modelEntity->setData('attempts', 1);
          $this->modelEntity->setData('link_generated_count', 1);
          $this->modelEntity->setData('reminder_sent_count', 0);
          $this->modelEntity->save();



      } catch (\Exception $e) {
          // Log the exception
          $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/lnapi_observer.log');
          $logger = new \Zend_Log();
          $logger->addWriter($writer);
          $logger->err($e->getMessage());
      }
      } else {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/lnapi_observer.log');
          $logger = new \Zend_Log();
          $logger->addWriter($writer);
          $logger->info('config is disable');
      }
      
    }

    private function sendEmail($email, $data)
    {
      
        $transport = $this->transportBuilder
            ->setTemplateIdentifier(34) // Email template ID
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ])
            // during Production update Email sender Name #Current Pharmacy planet
            ->setTemplateVars($data)
            ->setFromByScope('general') // Sender email as defined in store settings
            ->addTo($email)
            ->getTransport();

        $transport->sendMessage();
    }

    
}

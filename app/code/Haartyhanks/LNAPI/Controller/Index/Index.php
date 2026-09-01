<?php

namespace Haartyhanks\LNAPI\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Haartyhanks\LNAPI\Helper\Soap;
use Magento\Sales\Api\OrderRepositoryInterface;

class Index extends Action
{
    protected $soapHelper;
    protected $orderRepository;

    public function __construct(
        Context $context,
        Soap $soapHelper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->soapHelper = $soapHelper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        // Get the order ID from the request (assuming it's passed as a parameter)
        $orderId = $this->getRequest()->getParam('order_id');

        if (!$orderId) {
            echo 'Order ID is required.';
            return;
        }

        try {
            // Load the order
            $order = $this->orderRepository->get($orderId);

            // Get customer details from the order
            $customerFirstname = $order->getCustomerFirstname();
            $customerLastname = $order->getCustomerLastname();
            $customerDob = $order->getCustomerDob(); // Assuming DOB is stored in the customer data
            $customerEmail = $order->getCustomerEmail();

            // Fallbacks if customer DOB or email is missing
            $customerDob = $customerDob ?: '1970-01-01'; // Default DOB if not available
            $customerEmail = $customerEmail ?: '';

            // Dynamic XML Payload
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
          <username xsi:type="xsd:string">20022937</username>
          <password xsi:type="xsd:string">AOrWbzgTc2FRTbE2THzTicojYoBm</password>
        </Login>
        <Person xsi:type="ns1:PersonDetails">
            <forename xsi:type="xsd:string">{$customerFirstname}</forename>
            <surname xsi:type="xsd:string">{$customerLastname}</surname>
            <dob xsi:type="xsd:string">{$customerDob}</dob>
            <mobile1 xsi:type="xsd:string"></mobile1>
            <email xsi:type="xsd:string">{$customerEmail}</email>
            <remotecheck xsi:type="tns:RemoteCheckRequest">
                <JourneyID xsi:type="xsd:string">377</JourneyID>
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

            // Call the SOAP helper to send the request
            $response = $this->soapHelper->sendSoapRequest($xmlPayload);

            // Display the response
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}

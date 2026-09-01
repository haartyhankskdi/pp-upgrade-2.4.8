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


class Check extends \Magento\Backend\App\Action
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

        $data = $this->loadEntityByCustomerId($params['customer_id']);

        echo "<pre>";
        
        exit();

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

    private function checkStatus(string $id, string $ikey): ?string
    {
        $data = [
            'ID' => $id,
            'IKey' => $ikey,
            'username' => '20023737',
            'password' => '17UR7xHicvoOvQCRPGoiPlZkbBGh',
            'action' => 'CHECK',
            'remotecheck' => true
        ];

        $xmlRequest = $this->buildXmlRequest($data);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://sandbox.ws-idu.tracesmart.co.uk/v5.11',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlRequest,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml'],
        ]);


        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/LN.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);

        $zendLogger->info('========================== Application status ==================================');
        $zendLogger->info($xmlRequest);


        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->logger->error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        try {
            $xml = new \SimpleXMLElement($response);
            $xml->registerXPathNamespace('ns4', 'urn:idu');
            $statusNode = $xml->xpath('//ns4:RemoteCheck/ns4:ApplicationStatus');
            $zendLogger->info('========================== Application Result` ==================================');

            $zendLogger->info($response);

            return isset($statusNode[0]) ? (string)$statusNode[0] : null;
        } catch (\Exception $e) {
            $this->logger->error('SOAP parsing error: ' . $e->getMessage());
            return null;
        }
    }

    private function buildXmlRequest(array $data): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:ns1="urn:idu"
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <SOAP-ENV:Body>
    <ns1:IDUProcess>
      <params xsi:type="ns1:Request" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        <IDU xsi:type="ns1:IDUDetails">
          <ID xsi:type="xsd:string">' . htmlspecialchars($data['ID']) . '</ID>
          <IKey xsi:type="xsd:string">' . htmlspecialchars($data['IKey']) . '</IKey>
        </IDU>
        <Login xsi:type="ns1:LoginDetails">
          <username xsi:type="xsd:string">' . htmlspecialchars($data['username']) . '</username>
          <password xsi:type="xsd:string">' . htmlspecialchars($data['password']) . '</password>
        </Login>
        <Person xsi:type="ns1:PersonDetails">
          <remotecheck xsi:type="ns1:RemoteCheckRequest">
            <action xsi:type="xsd:string">' . htmlspecialchars($data['action']) . '</action>
          </remotecheck>
        </Person>
        <Services xsi:type="ns1:ServiceDetails">
          <remotecheck xsi:type="xsd:boolean">' . ($data['remotecheck'] ? 'true' : 'false') . '</remotecheck>
        </Services>
      </params>
    </ns1:IDUProcess>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
    }

/**
 * Load entity model data by customer ID
 *
 * @param int $customerId
 * @return \Haartyhanks\LNAPI\Model\Entity|null
 */
private function loadEntityByCustomerId($customerId)
{

    // Use the Entity Model Factory to create a collection and filter by customer_id
    $entityCollection = $this->_entityFactory->create()->getCollection()
        ->addFieldToFilter('customer_id', $customerId)
        ->setPageSize(1);

    $entity = $entityCollection->getFirstItem();
    if ($entity && $entity->getId()) {
        return $entity;
    }
    return null;
}
}

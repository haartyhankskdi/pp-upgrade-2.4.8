<?php
declare(strict_types=1);

namespace Haartyhanks\LNAPI\Cron;

use Psr\Log\LoggerInterface;
use Haartyhanks\LNAPI\Model\ResourceModel\Entity\CollectionFactory as EntityCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Email\Model\Template as CoreTemplate;
use Haartyhanks\LNAPI\Model\EntityFactory as EntityModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\CustomerFactory;
use Haartyhanks\LNAPI\Helper\System;

/**
 * Cron job to check API status and send email notifications
 */
class Mail
{
    private LoggerInterface $logger;
    private EntityCollectionFactory $entityCollectionFactory;
    private CustomerRepositoryInterface $customerRepository;
    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private TimezoneInterface $timezone;
    private CoreTemplate $coreTemplate;
    private EntityModel $entityModel;
    protected $customerModel;
    protected $system;

    public function __construct(
        LoggerInterface $logger,
        EntityCollectionFactory $entityCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        CoreTemplate $coreTemplate,
        EntityModel $entityModel,
        CustomerFactory $customerModel,
        System $system
    ) {
        $this->logger = $logger;
        $this->entityCollectionFactory = $entityCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->timezone = $timezone;
        $this->coreTemplate = $coreTemplate;
        $this->entityModel = $entityModel;
        $this->customerModel = $customerModel;
        $this->system = $system;
    }

    /**
     * Cron entry point
     */
    public function execute(): void
    {


       


        $this->logger->info('LNAPI Cron started.');
      
        try {
            $collection = $this->entityCollectionFactory->create();
            $collection->addFieldToSelect('*')
                ->addFieldToFilter('status', ['eq' => 'PENDING']);

            $this->logger->info(sprintf('Total Pending Records: %d', $collection->getSize()));

            foreach ($collection as $entity) {
                $this->logger->info('Processing entity ID: ' . $entity->getId());

                $applicationStatus = $this->checkStatus(
                    $entity->getData('remote_check_id'),
                    $entity->getData('remote_check_key')
                );

                $applicationStatus = $entity->getData('status');
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
                $zendLogger = new \Zend_Log();
                $zendLogger->addWriter($writer);
                
                $zendLogger->info("application Status". $applicationStatus);
                $zendLogger->info('========================== Application status ==================================');
                $zendLogger->info(print_r($entity->getData(), true));
              
                switch ($applicationStatus) {
                    case 'FAIL':
                        echo "Application is COMPLETE for entity ID: " . $entity->getId();
                        $this->logger->info("Application is COMPLETE for entity ID: " . $entity->getId());
                        
                        $this->failedJob($entity);

                        break;

                    case 'COMPLETE':
                        echo "Application is COMPLETE for entity ID: " . $entity->getId();
                        $this->logger->info("Application is COMPLETE for entity ID: " . $entity->getId());
                        
                        $this->completeJob($entity);
                        break;

                    case 'PENDING':
                        echo "Application is PENDING for entity ID: " . $entity->getId();
                        $this->logger->info("Application is PENDING for entity ID: " . $entity->getId());
                        $this->pendingJob($entity);
                        break;

                    default:
                        echo "Application is in UNKNOWN state for entity ID: " . $entity->getId();
                        $this->logger->warning("Application is in UNKNOWN state for entity ID: " . $entity->getId());
                }
            }

            $this->logger->info('LNAPI Cron completed successfully.');
        } catch (\Exception $e) {
            $this->logger->error('LNAPI Cron failed: ' . $e->getMessage());
        }
    }

    /**
     * Marks an entity as complete and sends a success email
     */
    private function completeJob($entity): void
    {
        try {
            $customerId = (int)$entity->getData('customer_id');
            $customer = $this->customerRepository->getById($customerId);

            $emailData = [
                'customer_name' => $customer->getFirstname(),
                'application_status' => 'COMPLETE',
                'brand_name' => 'X Pharma Ltd',
            ];
            $this->sendEmail($customer->getEmail(), $emailData, 'application_complete_template');

            $model = $this->entityModel->create()->load($entity['entity_id']);
            $model->setData('status', 'COMPLETE');
            $model->setData('is_verified', 1);

            $this->updateCustomerCustomAttribute($customerId, 'ln_kyc', 1);
            $this->updateCustomerCustomAttribute($customerId, 'age_verified', 1);
            $this->updateCustomerCustomAttribute($customerId, 'document_assessment', 1);
            $this->updateCustomerCustomAttribute($customerId, 'facial_match', 1);
            $this->updateCustomerCustomAttribute($customerId, 'liveness', 1);


            $model->save();

            $this->logger->info(sprintf(
                'Marked COMPLETE and sent email for entity ID: %d',
                $entity['entity_id']
            ));
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Customer not found for entity ID {$entity['entity_id']}");
        } catch (\Exception $e) {
            $this->logger->error('Error in completeJob: ' . $e->getMessage());
        }
    }

    /**
     * Handles pending jobs, checks if reminder needs to be sent
     */
    private function pendingJob($entity): void
    {
        try {

            $date = $entity['updated_at'] ? $entity['updated_at'] : $entity['first_link_generated_at'];

            // if (!$this->isThreeDaysOld($date)) {
            //     $this->logger->info("No reminder needed yet for entity ID {$entity['entity_id']}");
            //     return;
            // }

            $this->logger->info("three days passed");
            

            $customerId = (int)$entity->getData('customer_id');
            $customer = $this->customerRepository->getById($customerId);

            // Check if the current link has expired
            $currentExpiry = $entity['current_link_expiry_at'];

            $emailData = [
                'customer_name' => $customer->getFirstname(),
                'invite_link' => $entity['verification_link'],
                'invite_expires_at' => $entity['current_link_expiry_at'],
                'application_status' => $entity['status'],
                'brand_name' => 'X Pharma Ltd',
            ];

            $URL = false;
            if ($currentExpiry && strtotime($currentExpiry) < time()) {
                
                $API_params = [];
                $API_params['forename'] = $customer->getFirstname();
                $API_params['surname'] = $customer->getLastname();
                $API_params['dob'] = $customer->getDob();
                $API_params['JourneyID'] = '637';
                $API_params['ID'] = '20023737';
                $API_params['IKey'] = '17UR7xHicvoOvQCRPGoiPlZkbBGh';
                

                $URL = $this->linkGenerationCall($API_params);

                $emailData['invite_link'] = $URL['inviteLink'];
                $emailData['invite_expires_at'] = $URL['inviteExpiresAt'];

                $this->logger->info("Verification link expired for entity ID {$entity['entity_id']}");
                return;
            }

           
            

            $this->sendEmail($customer->getEmail(), $emailData, 'application_reminder_template');

            $model = $this->entityModel->create()->load($entity['entity_id']);
            if ($URL['status']){
                $model->setData('verification_link', $URL['inviteLink'] );
                $model->setData('current_link_expiry_at', $URL['inviteExpiresAt'] );

            }
            $model->setData('reminder_sent_count', $entity['reminder_sent_count'] + 1);
            $model->save();

            $this->logger->info(sprintf(
                'Reminder email sent for entity ID %d to %s',
                $entity['entity_id'],
                $customer->getEmail()
            ));
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Customer not found for entity ID {$entity['entity_id']}");
        } catch (\Exception $e) {
            $this->logger->error('Error in pendingJob: ' . $e->getMessage());
        }
    }

    /**
     * Calls external SOAP API to check status
     */
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
        $zendLogger->info( $xmlRequest);


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

    /**
     * Builds XML for SOAP request
     */
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
     * Sends email with given template
     */
    private function sendEmail(string $toEmail, array $data, int $templateId): void
    {
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ])
                ->setTemplateVars($data)
                ->setFromByScope('general')
                ->addTo($toEmail)
                ->getTransport();

            $transport->sendMessage();
        } catch (MailException|LocalizedException $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage());
        }
    }

    /**
     * Checks if given date is exactly 3 days old
     */
    private function isThreeDaysOld(string $date): bool
    {
        try {
            $now = $this->timezone->date();
            $givenDate = $this->timezone->date(new \DateTime($date));
            $interval = $givenDate->diff($now);
            return $interval->days === 3;
        } catch (\Exception $e) {
            $this->logger->error('Date check error: ' . $e->getMessage());
            return false;
        }
    }

    public function linkGenerationCall($data) {
        $xmlRequest = $this->xmlBodyForLinkGeneration($data);
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://sandbox.ws-idu.tracesmart.co.uk/v5.11',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlRequest,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml'],
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->logger->error('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

      
        $xml = new \SimpleXMLElement($response);

        // Register namespaces
        $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ns4', 'urn:idu');
        
        // Extract values
        $status = (string)$xml->xpath('//Summary/Status')[0];
        $inviteLink = (string)$xml->xpath('//RemoteCheck/InviteLink')[0];
        $inviteExpiresAt = (string)$xml->xpath('//RemoteCheck/InviteExpiresAt')[0];
       
        $responseBody = ['status'=> false, 'inviteLink' => '' , 'inviteExpiresAt'=> ''];
       
        if ($status) {
            $responseBody['status'] = $status;
            $responseBody['inviteLink'] = $inviteLink;
            $responseBody['inviteExpiresAt'] = $inviteExpiresAt;

            return $responseBody;
        }

        return $responseBody;
     
    }

    private function failedJob($entity): void
    {
        try {
            $customerId = (int)$entity->getData('customer_id');
            $customer = $this->customerRepository->getById($customerId);

            $reminderCount = (int)$entity['reminder_sent_count'];
            $emailData = [
                'customer_name' => $customer->getFirstname(),
                'brand_name' => 'X Pharma Ltd',
                'application_status' => 'FAILED',
            ];

            if ($reminderCount < 3) {
                // Attempt to generate new verification link
                $API_params = [
                    'forename'   => $customer->getFirstname(),
                    'surname'    => $customer->getLastname(),
                    'dob'        => $customer->getDob(),
                    'JourneyID'  => '637',
                    'ID'         => '20023737',
                    'IKey'       => '17UR7xHicvoOvQCRPGoiPlZkbBGh',
                ];

                $URL = $this->linkGenerationCall($API_params);

                if ($URL && !empty($URL['inviteLink'])) {
                    $emailData['invite_link'] = $URL['inviteLink'];
                    $emailData['invite_expires_at'] = $URL['inviteExpiresAt'];
                    $this->sendEmail($customer->getEmail(), $emailData, 'application_failed_template');

                    // Save new link to entity
                    $model = $this->entityModel->create()->load($entity['entity_id']);
                    $model->setData('verification_link', $URL['inviteLink']);
                    $model->setData('current_link_expiry_at', $URL['inviteExpiresAt']);
                    $model->setData('reminder_sent_count', $reminderCount + 1);
                    $model->save();

                    $this->logger->info(sprintf(
                        'Failed status: new verification link sent for entity ID %d to %s',
                        $entity['entity_id'],
                        $customer->getEmail()
                    ));
                } else {
                    $this->logger->error("FailedJob: Could not generate new link for entity ID {$entity['entity_id']}");
                }
            } else {
                // 3 attempts reached: Inform the user to contact their prescriber
                $emailData['contact_message'] = 'Your verification link has expired and cannot be reissued automatically. Please contact your prescriber for further assistance.';
                $this->sendEmail($customer->getEmail(), $emailData, 'application_failed_contact_prescriber_template');

                $model = $this->entityModel->create()->load($entity['entity_id']);
                $model->setData('status', 'FAILED');
                $model->setData('is_verified', 0);
                $model->save();

                $this->logger->info(sprintf(
                    'FailedJob: 3 attempts reached for entity ID %d. Asked to contact prescriber.',
                    $entity['entity_id']
                ));
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Customer not found for entity ID {$entity['entity_id']}");
        } catch (\Exception $e) {
            $this->logger->error('Error in failedJob: ' . $e->getMessage());
        }
    }

    /**
     * Builds XML for SOAP request for link generation
     */
    public function xmlBodyForLinkGeneration(array $data): string
    {
            $id = htmlspecialchars($data['ID'] ?? '');
            $ikey = htmlspecialchars($data['IKey'] ?? '');
            $forename = htmlspecialchars($data['forename'] ?? '');
            $surname = htmlspecialchars($data['surname'] ?? '');
            $dob = htmlspecialchars($data['dob'] ?? '');
            $journeyId = htmlspecialchars($data['JourneyID'] ?? '');

            return '<?xml version="1.0" encoding="UTF-8"?>
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
                <username xsi:type="xsd:string">' . $id . '</username>
                <password xsi:type="xsd:string">' . $ikey . '</password>
                </Login>
                <Person xsi:type="ns1:PersonDetails">
                    <forename xsi:type="xsd:string">' . $forename . '</forename>
                    <surname xsi:type="xsd:string">' . $surname . '</surname>
                    <dob xsi:type="xsd:string">' . $dob . '</dob>
                    <mobile1 xsi:type="xsd:string"></mobile1>
                    <email xsi:type="xsd:string"></email>
                    <remotecheck xsi:type="tns:RemoteCheckRequest">
                        <JourneyID xsi:type="xsd:string">' . $journeyId . '</JourneyID>
                    </remotecheck>
                </Person>
                <Services xsi:type="ns1:ServiceDetails">
                <remotecheck>true</remotecheck>
                </Services>
            </params>
            </ns1:IDUProcess>
        </SOAP-ENV:Body>
        </SOAP-ENV:Envelope>';
        }


    /**
     * Update a custom attribute for a customer using customer id.
     *
     * @param int $customerId
     * @param string $attributeCode
     * @param mixed $attributeValue
     * @return bool
     */
    private function updateCustomerCustomAttribute($customerId, $attributeCode, $attributeValue): bool
    {
        try {
            // Load customer by customerId
            $customer = $this->customerModel->create()->load($customerId);

            if (!$customer || !$customer->getId()) {
                $this->logger->error("Customer with ID $customerId does not exist.");
                return false;
            }

            // Set custom attribute value
            $customer->setData($attributeCode, $attributeValue);

            // Save customer
            $customer->save();

            $this->logger->info("Updated $attributeCode for customer ID $customerId to '$attributeValue'.");

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                "Error updating $attributeCode for customer $customerId: " . $e->getMessage()
            );
            return false;
        }
    }

}

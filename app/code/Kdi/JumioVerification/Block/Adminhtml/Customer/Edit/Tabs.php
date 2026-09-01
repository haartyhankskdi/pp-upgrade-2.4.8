<?php

namespace Kdi\JumioVerification\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Kdi\JumioVerification\Model\JumioVerificationFactory;
use Magento\Framework\App\RequestInterface;

class Tabs extends \Magento\Backend\Block\Template implements \Magento\Ui\Component\Layout\Tabs\TabInterface
{
    protected $_template = 'tab/customtab_view.phtml';//your template file path
    
     /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
    * @var JumioVerificationFactory
    */
    protected $jumioVerificationFactory;

    protected $request;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        CustomerRepositoryInterface $customerRepository,
        JumioVerificationFactory $jumioVerificationFactory,
        RequestInterface $request,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerRepository = $customerRepository;
        $this->jumioVerificationFactory = $jumioVerificationFactory;
        $this->request = $request;
        parent::__construct($context, $data);
    }
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(\Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID);
    }
    public function getTabLabel()
    {
        return __('Jumio Status');
    }
    public function getTabTitle()
    {
        return __('Jumio Status');
    }

    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }
    public function getTabClass()
    {
        return '';
    }

    public function getTabUrl()
    {
        return '';
    }
    public function isAjaxLoaded()
    {
        return false;
    }
    public function getStatus()
    {
        $response = $this->getWorkflowExecutionDetails();
        $result = 'Not Opened Link';
        if (!is_null($response)) {
            $data = json_decode($response, true);
            $status = $data['workflow']['status'];

            if ($status === 'PROCESSED') {
                $finalStatus = $data['decision']['type'];
                if ($finalStatus === 'PASSED' || $finalStatus === 'WARNING') {

                    $result = 'Jumio Verified Successfully';

                } else {

                    $result = 'Rejected';
                }
            } elseif ($status === 'INITIATED') {

                $result = 'Not Executed';

            } elseif ($status === 'SESSION_EXPIRED') {
                $result = 'Rejected';
            }

            $jumioVerificationData  = $this->getJumioVerificationData();
            if (!is_null($jumioVerificationData)) {
                $jumioVerificationData->setStatus($result);
                $jumioVerificationData->save();
            }
            return $result;
        }
    }

    /**
    * Get Jumio Verification information for a given customer email
    *
    * @return \Kdi\JumioVerification\Model\JumioVerification|null
    */
    public function getJumioVerificationData()
    {
        $params = $this->request->getParams();
        $customerId = isset($params['id']) ? $params['id'] : null;
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerEmail = $customer->getEmail();
            // $customerEmail = 'harshada@haartyhanks.com';

            $jumioVerification = $this->jumioVerificationFactory->create();
            $collection = $jumioVerification->getCollection();
            $collection->addFieldToFilter('customer_email', $customerEmail);

            if ($collection->getSize() === 0) {
                return null;
            }

            return $collection->getFirstItem();

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Post API response
     *
     * @return string|null
     */
    public function getInitiate()
    {
        $data = array(
            'customerInternalReference' => 'KYX-API-TEST',
            'userReference' => 'test-user-1',
            'workflowDefinition' => array(
                'key' => 10011
            )
        );
        
        $data_string = json_encode($data);
        
        $ch = curl_init('https://account.emea-1.jumio.ai/api/v1/accounts');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'User-Agent: {{user-agent}}',
            'Authorization: Basic OTYwNjk1OGQtOGFlYy00MWM0LWJjMjAtM2YwZjFlMzY5MTczOlFMeGw3VnUzVVNBNm9sNGlMdGJJVmdkZ0FKV3JCWDRX'
        ));
        
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response) {
            return $response;
        } else {
            return null;
        }
    }

    public function getWorkflowExecutionDetails()
    {
        $curl = curl_init();
        
        $jumioVerificationData  = $this->getJumioVerificationData();
        if (!is_null($jumioVerificationData)) {

            $response = $jumioVerificationData->getData();

            # get the workflowId value
            $workflowId = trim($response['workflow_id']);

            $url = "https://retrieval.emea-1.jumio.ai/api/v1/workflow-executions/".$workflowId;

            if (isset($url)) {

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: {{user-agent}}',
                    'Authorization: Basic OTYwNjk1OGQtOGFlYy00MWM0LWJjMjAtM2YwZjFlMzY5MTczOlFMeGw3VnUzVVNBNm9sNGlMdGJJVmdkZ0FKV3JCWDRX'
                ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);

                if ($response) {
                    return $response;
                } else {
                    return null;
                }
            }
        }
    }
}
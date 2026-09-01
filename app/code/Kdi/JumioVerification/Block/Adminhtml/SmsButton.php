<?php

namespace Kdi\JumioVerification\Block\Adminhtml;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Kdi\JumioVerification\Model\JumioVerificationFactory;

class SmsButton implements ButtonProviderInterface
{
    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $curl;
    protected $request;

     /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
    * @var JumioVerificationFactory
    */
    protected $jumioVerificationFactory;

    /**
    * Data constructor.
    *
    * @param \Magento\Framework\App\Helper\Context $context
    * @param \Magento\Framework\HTTP\Client\Curl $curl
    */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        JumioVerificationFactory $jumioVerificationFactory
    ) {
        $this->curl = $curl;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->jumioVerificationFactory = $jumioVerificationFactory;
        // parent::__construct($context);
    }

    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $response = $this->getWorkflowExecutionDetails();
        if (!is_null($response)) {
            $data = json_decode($response, true);
            $status = $data['workflow']['status'];

            if ($status === 'PROCESSED') {
                $finalStatus = $data['decision']['type'];
                if ($finalStatus === 'PASSED' || $finalStatus === 'WARNING') {

                    $data = [
                        'label' => __('Jumio Verified Successfully'),
                        'class' => 'add',
                        'sort_order' => 10
                    ];

                    $jumioVerificationData  = $this->getJumioVerificationData();
                    if (!is_null($jumioVerificationData)) {
                        $jumioVerificationData->setStatus($finalStatus);
                        $jumioVerificationData->save();
                    }
                } else {
                    $data = [
                        'label' => __('Rejected'),
                        'class' => 'add',
                        // 'style' => 'background-color: #eb5202, border-color: #eb5202, color: #ffffff, text-shadow: 1px 1px 0 rgb(0 0 0 / 25%);',
                        'sort_order' => 10
                    ];
                }
            } elseif ($status === 'INITIATED') {
                $data = [
                    'label' => __('Not Executed'),
                    'class' => 'add',
                    'sort_order' => 10
                ];

                $jumioVerificationData  = $this->getJumioVerificationData();
                if (!is_null($jumioVerificationData)) {
                    $jumioVerificationData->setStatus($status);
                    $jumioVerificationData->save();
                }

            } elseif ($status === 'SESSION_EXPIRED') {
                $data = [
                    'label' => __('Rejected'),
                    'class' => 'add',
                    'sort_order' => 10
                ];

                $jumioVerificationData  = $this->getJumioVerificationData();
                if (!is_null($jumioVerificationData)) {
                    $jumioVerificationData->setStatus($status);
                    $jumioVerificationData->save();
                }
            }
            return $data;
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
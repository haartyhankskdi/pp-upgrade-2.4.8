<?php

namespace Fetchify\Fetchify\Controller\Load;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Directory\Model\RegionFactory;

class Address extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    protected $regionFactory;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RegionFactory $regionFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->regionFactory = $regionFactory;
        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $postcode = $this->getRequest()->getParam('postcode');  
        
        $cleanPostcode = preg_replace('/[^a-zA-Z0-9]/', '', $postcode);
        $address = $this->getAddress($cleanPostcode);
        return $resultJson->setData($address);
    }


    /**
     * Get address from Fetchify
     * @param string $postcode
     * @return array
     */
    public function getAddress($postcode)
    {
        $baseUrl = 'https://pcls1.craftyclicks.co.uk/json/rapidaddress';
        $apiKey  = '909aa-ff3c9-94df0-0a6db';
    
        $params = [
            'key'      => $apiKey,
            'postcode' => $postcode,
            'sort'     => 'asc',
            'response' => 'data_formatted',
            'lines'    => 2
        ];
    
        $url = $baseUrl . '?' . http_build_query($params);
    
        $curl = curl_init();
    
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
    
        $response = curl_exec($curl);
    
        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            return [
                'success' => false,
                'error'   => $error
            ];
        }
    
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        $data = json_decode($response, true);

        // print_r($data);

        if( array_key_exists('error_code', $data)){
            return [
                'http_code' => $data['error_code'],
                'status'    => 'false',
                'error_msg' => $data['error_msg'] 
                
            ]; 
        }
        

        $county = $data['postal_county'] !='' ? $data['postal_county'] : $data['traditional_county'];
        $county = $this->normalizeWords($county);
        return [
            'http_code' => $httpCode,
            'status'    => 'sucsess',
            'error'     => $data['error'] ?? null,
            'response'  => $data,
            'county' => $county,
            'region_id' =>$this->getRegionIdByLabelAndCountry($county, 'GB')
        ];
    }

public function getRegionIdByLabelAndCountry($regionLabel, $countryCode)
{

    return $this->regionFactory->create()->loadByCode($regionLabel, $countryCode)->getRegionId();
}

public function normalizeWords(string $str): string {
    $str = strtolower(trim($str));   // all lower + trim spaces
    return ucwords($str);            // first letter of each word uppercase
}
}
<?php

namespace Fetchify\Fetchify\Controller\Get;

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
        $query = $this->getRequest()->getParam('query');     
        $address = $this->getAddress($query);
        return $resultJson->setData($address);
    }


    /**
     * Get address from Fetchify
     * @param string $query
     * @return array
     */
    public function getAddress($query)
    {

        $payload = [
            "key" => "909aa-ff3c9-94df0-0a6db",
            "query" => $query,
            "id" => "",
            "country" => "gbr",
            "fingerprint" => "21a86f9f27bbee",
            "integration" => "magento2",
            "js_version" => "1.16.1",
            "sequence" => 1,
            "type" => 0,
            "coords" => new \stdClass(),
            "extra" => [
                "exclude_areas" => [""]
            ]
        ];
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.craftyclicks.co.uk/address/1.1/find',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_POSTFIELDS =>json_encode($payload),
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));
        
        $response = curl_exec($curl);
        
        // Get HTTP response status code
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response =json_decode($response, true);
        curl_close($curl);
        if($httpCode == 200){
            return [
            'status' => 200,
            'response' => $response,
            'message' => 'data recieved'
            ];
        } else {
            return [
                'status' => 400,
                'message' => 'Address not available'
                ];
        }


        
    }

}
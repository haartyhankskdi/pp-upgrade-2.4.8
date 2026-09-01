<?php

namespace Haartyhanks\LNAPI\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Soap extends AbstractHelper
{
    public function sendSoapRequest($xmlPayload)
    {
        $url = 'https://sandbox.ws-idu.tracesmart.co.uk/v5.10';
        $headers = [
            'Content-Type: text/xml',
            'Content-Length: ' . strlen($xmlPayload),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

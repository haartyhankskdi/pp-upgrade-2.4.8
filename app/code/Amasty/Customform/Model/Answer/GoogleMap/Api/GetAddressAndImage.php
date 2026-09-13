<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer\GoogleMap\Api;

use Amasty\Customform\Helper\Data;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields\Googlemap;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils as GuzzleUtils;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use GuzzleHttp\ClientFactory as GuzzleClientFactory;

class GetAddressAndImage
{
    public const GOOGLE_API_ENDPOINT = 'https://maps.googleapis.com/maps/api/';
    public const API_KEY_PARAMETER = 'key';

    /**
     * @var StaticMapApi
     */
    private $staticMapApi;

    /**
     * @var GeocodingApi
     */
    private $geocodingApi;

    public function __construct(
        StaticMapApi $staticMapApi,
        GeocodingApi $geocodingApi
    ) {
        $this->staticMapApi = $staticMapApi;
        $this->geocodingApi = $geocodingApi;
    }

    public function execute(float $longitude, float $latitude): DataObject
    {
        $requests = [
            Googlemap::ADDRESS => $this->geocodingApi->requestByCoordinates($longitude, $latitude),
            Googlemap::IMAGE => $this->staticMapApi->requestByCoordinates($longitude, $latitude)
        ];
        $result = array_map([$this, 'getResultContent'], GuzzleUtils::unwrap($requests));

        return new DataObject([
            Googlemap::IMAGE => $this->staticMapApi->processResponse($result[Googlemap::IMAGE]),
            Googlemap::ADDRESS => $this->geocodingApi->processResponse($result[Googlemap::ADDRESS])
        ]);
    }

    private function getResultContent(Response $response): string
    {
        return $response->getBody()->getContents();
    }
}

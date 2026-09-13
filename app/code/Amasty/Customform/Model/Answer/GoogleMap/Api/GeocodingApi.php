<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer\GoogleMap\Api;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Helper\Data;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Promise\PromiseInterface;

class GeocodingApi implements RequestByCoordinatesInterface, ResponseProcessorInterface
{
    public const LAT_LNG = 'latlng';
    public const FORMATTED_ADDRESS = 'formatted_address';
    public const ENDPOINT = 'geocode/json';
    public const RESULTS = 'results';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Data
     */
    private $configProvider;

    /**
     * @var array
     */
    private $requestConfig;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        ClientFactory $clientFactory,
        Data $configProvider,
        Serializer $serializer,
        array $requestConfig = []
    ) {
        $this->clientFactory = $clientFactory;
        $this->configProvider = $configProvider;
        $this->requestConfig = $requestConfig;
        $this->serializer = $serializer;
    }

    public function requestByCoordinates(float $longitude, float $latitude): PromiseInterface
    {
        $client = $this->clientFactory->create(['config' => ['base_uri' => GetAddressAndImage::GOOGLE_API_ENDPOINT]]);

        return $client->getAsync(self::ENDPOINT, ['query' => $this->getQuery($longitude, $latitude)]);
    }

    public function processResponse(string $response): string
    {
        try {
            $parsedResponse = $this->serializer->unserialize($response);
            $result = $this->findAddress($parsedResponse);
        } catch (\Exception $e) {
            $result = '';
        }

        return $result;
    }

    private function findAddress(array $googleResponse): string
    {
        $result = '';
        $addressParts = $googleResponse[self::RESULTS] ?? null;

        if (is_array($addressParts) && !empty($addressParts)) {
            $addressPart = array_first($addressParts);

            if (!empty($addressPart[self::FORMATTED_ADDRESS])) {
                $result = $addressPart[self::FORMATTED_ADDRESS];
            }
        }

        return $result;
    }

    private function getQuery(float $longitude, float $latitude): array
    {
        $params = [
            GetAddressAndImage::API_KEY_PARAMETER => $this->configProvider->getGoogleKey(),
            self::LAT_LNG => sprintf('%.6F,%.6F', $latitude, $longitude)
        ];

        return array_merge($this->requestConfig, $params);
    }
}

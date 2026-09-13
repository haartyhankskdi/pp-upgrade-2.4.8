<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer\GoogleMap\Api;

use Amasty\Customform\Helper\Data;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Promise\PromiseInterface;

class StaticMapApi implements RequestByCoordinatesInterface, ResponseProcessorInterface
{
    public const SIZE = 'size';
    public const ZOOM = 'zoom';
    public const CENTER = 'center';
    public const MARKERS = 'markers';
    public const ENDPOINT = 'staticmap';

    public const DEFAULT_CONFIG = [
        self::SIZE => '600x300',
        self::ZOOM => 15,
        self::MARKERS => 'color:red|size:mid|%s'
    ];

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

    public function __construct(
        ClientFactory $clientFactory,
        Data $configProvider,
        array $requestConfig = []
    ) {
        $this->clientFactory = $clientFactory;
        $this->configProvider = $configProvider;
        $this->requestConfig = array_merge(self::DEFAULT_CONFIG, $requestConfig);
    }

    public function requestByCoordinates(float $longitude, float $latitude): PromiseInterface
    {
        $client = $this->clientFactory->create(['config' => ['base_uri' => GetAddressAndImage::GOOGLE_API_ENDPOINT]]);

        return $client->getAsync(self::ENDPOINT, ['query' => $this->getQuery($longitude, $latitude)]);
    }

    public function processResponse(string $response): string
    {
        $encodedImage = base64_encode($response);

        return sprintf('data:image/png;base64,%s', $encodedImage);
    }

    private function buildCoordinates(float $longitude, float $latitude): string
    {
        return sprintf('%.6F,%.6F', $latitude, $longitude);
    }

    private function getQuery(float $longitude, float $latitude): array
    {
        $coordinates = $this->buildCoordinates($longitude, $latitude);
        $params = [
            GetAddressAndImage::API_KEY_PARAMETER => $this->configProvider->getGoogleKey(),
            self::MARKERS => sprintf($this->requestConfig[self::MARKERS], $coordinates),
            self::CENTER => $coordinates
        ];

        return array_merge($this->requestConfig, $params);
    }
}

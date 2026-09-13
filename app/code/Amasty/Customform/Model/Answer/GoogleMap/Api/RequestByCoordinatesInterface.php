<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer\GoogleMap\Api;

use GuzzleHttp\Promise\PromiseInterface;

interface RequestByCoordinatesInterface
{
    /**
     * @param float $longitude
     * @param float $latitude
     * @return PromiseInterface
     */
    public function requestByCoordinates(float $longitude, float $latitude): PromiseInterface;
}

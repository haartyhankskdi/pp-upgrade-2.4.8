<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer\GoogleMap\Api;

interface ResponseProcessorInterface
{
    /**
     * @param string $response
     * @return string
     */
    public function processResponse(string $response): string;
}

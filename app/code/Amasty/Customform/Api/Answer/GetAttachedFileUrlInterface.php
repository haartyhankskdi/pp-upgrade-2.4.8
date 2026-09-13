<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Api\Answer;

/**
 * @api
 */
interface GetAttachedFileUrlInterface
{
    /**
     * If store id is not passed, current store will be used
     *
     * @param string $fileName
     * @param int|null $storeId
     * @return string
     */
    public function execute(string $fileName, ?int $storeId = null): string;
}

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Api\Answer;

use Amasty\Customform\Exceptions\AttachedFileDoesNotExistsException;

/**
 * @api
 */
interface AttachedFileDataProviderInterface
{
    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getPath(string $fileName): string;

    /**
     * @param string $fileName
     *
     * @return string
     * @throws AttachedFileDoesNotExistsException
     */
    public function getContents(string $fileName): string;

    /**
     * @param string $fileName
     * @param int|null $storeId
     *
     * @return string
     */
    public function getUrl(string $fileName, ?int $storeId = null): string;
}

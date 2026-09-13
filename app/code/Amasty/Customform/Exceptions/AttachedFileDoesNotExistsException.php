<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Exceptions;

use Magento\Framework\Exception\LocalizedException;

class AttachedFileDoesNotExistsException extends LocalizedException
{
    //phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction
    public static function forFile(string $fileName): AttachedFileDoesNotExistsException
    {
        return new AttachedFileDoesNotExistsException(__('Attached file %1 does not exists.', $fileName));
    }
}

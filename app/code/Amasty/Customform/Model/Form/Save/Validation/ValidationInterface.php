<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Validation;

use Magento\Framework\Exception\LocalizedException;

interface ValidationInterface
{
    /**
     * @param array $formData
     * @throws LocalizedException
     */
    public function validate(array $formData): void;
}

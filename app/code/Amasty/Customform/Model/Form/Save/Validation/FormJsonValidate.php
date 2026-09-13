<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Validation;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\Exception\LocalizedException;

class FormJsonValidate implements ValidationInterface
{
    public function validate(array $formData): void
    {
        if (isset($formData[FormInterface::FORM_JSON]) && mb_strlen($formData[FormInterface::FORM_JSON]) > 65535) {
            throw new LocalizedException(
                __('The form content exceeds the maximum allowed length of 65,535 characters.')
            );
        }
    }
}

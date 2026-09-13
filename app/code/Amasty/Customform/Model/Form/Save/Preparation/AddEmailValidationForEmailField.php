<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Preparation;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Helper\Data as FormHelper;

class AddEmailValidationForEmailField implements PreparationInterface
{
    public const EMAIL_VALIDATION = 'validate-email';

    /**
     * @var FormHelper
     */
    private $moduleHelper;

    public function __construct(
        FormHelper $moduleHelper
    ) {
        $this->moduleHelper = $moduleHelper;
    }

    public function prepare(array $formData): array
    {
        if (!empty($formData[FormInterface::EMAIL_FIELD])) {
            $this->addEmailValidationOnEmailField($formData);
        }

        return $formData;
    }

    private function addEmailValidationOnEmailField(array &$data): void
    {
        $formJson = $data[FormInterface::FORM_JSON] ?? null;

        if ($formJson) {
            $emailField = $data[FormInterface::EMAIL_FIELD] ?? '';

            if ($emailField) {
                $formConfig = $this->moduleHelper->decode($formJson);
                $this->findAndReplaceEmailValidation($formConfig, $emailField);
                $data[FormInterface::FORM_JSON] = $this->moduleHelper->encode($formConfig);
            }
        }
    }

    private function findAndReplaceEmailValidation(array &$data, string $fieldName): void
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                if (!empty($value['name']) && $value['name'] === $fieldName) {
                    $value['validation'] = self::EMAIL_VALIDATION;
                } else {
                    $this->findAndReplaceEmailValidation($value, $fieldName);
                }
            }
        }
    }
}

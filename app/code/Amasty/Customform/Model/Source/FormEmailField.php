<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Source;

use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\FormRegistry;
use Amasty\Customform\Model\Submit;
use Magento\Framework\Data\OptionSourceInterface;

class FormEmailField implements OptionSourceInterface
{
    public const INITIAL_VALUE = 'initial_value';
    public const TEXT_FILED_TYPE = 'textinput';
    public const NAME = 'name';

    /**
     * @var FormRegistry
     */
    private $formRegistry;

    public function __construct(
        FormRegistry $formRegistry
    ) {
        $this->formRegistry = $formRegistry;
    }

    public function toOptionArray(): array
    {
        $options = [
            self::INITIAL_VALUE => [
                'label' => __('-- Please select --'),
                'value' => ''
            ]
        ];

        return array_merge($options, $this->getFormFields());
    }

    private function getFormFields(): array
    {
        $formFields = [];
        $currentForm = $this->formRegistry->getCurrentForm();

        if ($currentForm instanceof FormInterface) {
            foreach ($currentForm->getDecodedFormJson() as $page) {
                if (empty($page[Submit::TYPE])) {
                    foreach ($page as $field) {
                        $this->extractEmailFields($field, $formFields);
                    }
                } else {
                    $this->extractEmailFields($page, $formFields);
                }
            }
        } else {
            $formFields = [
                self::INITIAL_VALUE => [
                    'label' => __('-- Please save this form first --'),
                    'value' => ''
                ]
            ];
        }

        return $formFields;
    }

    private function extractEmailFields(array $fieldData, array &$destination): void
    {
        $type = $fieldData[Submit::TYPE] ?? '';

        if ($type === self::TEXT_FILED_TYPE) {
            $destination[] = [
                'label' => $fieldData[Submit::LABEL] ?? '',
                'value' => $fieldData[self::NAME] ?? ''
            ];
        }
    }
}

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\ProviderInterface;
use Amasty\Customform\Model\Submit;

class Processor implements ProcessorInterface
{
    public const SORT_ORDER = 'sort_order';
    public const PROCESSOR = 'processor';

    /**
     * @var VariablesProcessorInterface
     */
    private $variablesProcessor;

    /**
     * @var ProviderInterface[]
     */
    private $variableValueProviders;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        VariablesProcessorInterface $variablesProcessor,
        Serializer $serializer,
        array $variableValueProviders = []
    ) {
        $this->variablesProcessor = $variablesProcessor;
        $this->variableValueProviders = $this->parseConfig($variableValueProviders);
        $this->serializer = $serializer;
    }

    public function process(FormInterface $form): array
    {
        $result = [];

        foreach ($this->getFormConfig($form) as $page) {
            foreach ($page as $fieldConfig) {
                if (!empty($fieldConfig[Submit::VALUE])) {
                    $data = $this->replaceVariables($fieldConfig[Submit::VALUE]);
                    $result[$fieldConfig['name'] ?? ''] = $data;
                }
            }
        }

        return $result;
    }

    private function replaceVariables(string $fieldValue): string
    {
        foreach ($this->variablesProcessor->extractVariables($fieldValue) as $variableName) {
            $isValueReplaced = false;

            foreach ($this->variableValueProviders as $variableProvider) {
                if ($variableProvider->isCanRetrieve($variableName)) {
                    $fieldValue = $this->variablesProcessor->insertVariable(
                        $fieldValue,
                        $variableName,
                        $variableProvider->getValue($variableName)
                    );
                    $isValueReplaced = true;

                    break;
                }
            }

            if (!$isValueReplaced) {
                $fieldValue = $this->variablesProcessor->insertVariable(
                    $fieldValue,
                    $variableName,
                    ''
                );
            }
        }

        return $fieldValue;
    }

    private function getFormConfig(FormInterface $form): array
    {
        $formJson = $form->getOrigFormJson();

        return $formJson ? $this->serializer->unserialize($formJson) : [];
    }

    private function parseConfig(array $variableValueProviders): array
    {
        usort($variableValueProviders, function (array $valueA, array $valueB) {
            $sortOrderA = $valueA[self::SORT_ORDER] ?? 0;
            $sortOrderB = $valueB[self::SORT_ORDER] ?? 0;

            return $sortOrderA <=> $sortOrderB;
        });

        return array_column($variableValueProviders, self::PROCESSOR);
    }
}

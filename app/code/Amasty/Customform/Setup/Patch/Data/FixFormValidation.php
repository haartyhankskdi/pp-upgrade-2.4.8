<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Setup\Patch\Data;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Model\Form;
use Amasty\Customform\Model\Submit;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Throwable as Throwable;

class FixFormValidation implements DataPatchInterface
{
    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        FormRepositoryInterface $formRepository,
        Serializer $serializer,
        State $appState
    ) {
        $this->formRepository = $formRepository;
        $this->serializer = $serializer;
        $this->appState = $appState;
    }

    public static function getDependencies(): array
    {
        return [
            ClearFormBookmarks::class,
            InstallExamples::class
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): FixFormValidation
    {
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this, 'execute']
        );

        return $this;
    }

    public function execute(): void
    {
        foreach ($this->formRepository->getList() as $form) {
            $this->fixForm($form);
            $this->formRepository->save($form);
        }
    }

    private function fixForm(FormInterface $form): void
    {
        try {
            /** @var Form $form **/
            $formConfig = $this->serializer->unserialize($form->getData(FormInterface::FORM_JSON));
        } catch (Throwable $e) {
            $formConfig = [];
        }

        $firstPage = reset($formConfig);

        if (!empty($firstPage[Submit::TYPE])) {
            $formConfig = [$formConfig];
        }

        $formConfig = $this->fixFieldsValidation($formConfig);
        $form->setFormJson($this->serializer->serialize($formConfig));
    }

    private function fixFieldsValidation(array $formConfig): array
    {
        foreach ($formConfig as &$formPage) {
            foreach ($formPage as &$fieldConfig) {
                $fieldValidation = $fieldConfig['validation'] ?? '';

                try {
                    $unSerializedValidation = $this->serializer->unserialize($fieldValidation);
                    $fixedValidation = $unSerializedValidation['validation'] ?? '';
                    $fieldConfig['required'] = $unSerializedValidation['required'] ?? '0';
                } catch (Throwable $e) {
                    $fixedValidation = $fieldValidation;
                }

                $fieldConfig['validation'] = $fixedValidation;
            }
        }

        return $formConfig;
    }
}

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Setup\Model;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Api\FormRepositoryInterface;
use Amasty\Customform\Model\FormFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class FormExamplesInstaller
{
    public const FORM_JSON_KEY = 'form_json';

    /**
     * @var FormExamplesProvider
     */
    private $examplesProvider;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var FormRepositoryInterface
     */
    private $formRepository;

    public function __construct(
        FormExamplesProvider $examplesProvider,
        Serializer $serializer,
        FormFactory $formFactory,
        DateTime $date,
        FormRepositoryInterface $formRepository
    ) {
        $this->examplesProvider = $examplesProvider;
        $this->serializer = $serializer;
        $this->formFactory = $formFactory;
        $this->date = $date;
        $this->formRepository = $formRepository;
    }

    public function installExamples(): void
    {
        foreach ($this->examplesProvider->getExampleFormsData() as $formData) {
            $formData = $this->prepareFormData($formData);
            $form = $this->createFormModel($formData);
            $this->formRepository->save($form);
        }
    }

    private function createFormModel(array $formData): FormInterface
    {
        $formModel = $this->formFactory->create();
        $formModel->setData($formData);
        $formModel->setCreatedAt($this->date->gmtDate());

        return $formModel;
    }

    private function prepareFormData(array $formData): array
    {
        $formJson = $formData[self::FORM_JSON_KEY] ?? [];
        $formData[self::FORM_JSON_KEY] = $this->serializer->serialize($formJson);

        return $formData;
    }
}

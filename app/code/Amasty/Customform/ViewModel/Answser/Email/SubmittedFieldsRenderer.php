<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Answser\Email;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Answer\AttachedFileDataProviderInterface;
use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Submit;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;

class SubmittedFieldsRenderer
{
    public const DEFAULT_TEMPLATE = 'Amasty_Customform::email/submitted_fields.phtml';
    public const TYPE_FILE = 'file';

    /**
     * @var FieldsDataProviderFactory
     */
    private $fieldsDataProviderFactory;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var string
     */
    private $emailFieldsTemplate;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var AttachedFileDataProviderInterface
     */
    private $attachedFileDataProvider;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    public function __construct(
        FieldsDataProviderFactory $fieldsDataProviderFactory,
        BlockFactory $blockFactory,
        Serializer $serializer,
        DataObjectFactory $dataObjectFactory,
        AttachedFileDataProviderInterface $attachedFileDataProvider,
        string $emailFieldsTemplate = self::DEFAULT_TEMPLATE
    ) {
        $this->fieldsDataProviderFactory = $fieldsDataProviderFactory;
        $this->blockFactory = $blockFactory;
        $this->emailFieldsTemplate = $emailFieldsTemplate;
        $this->serializer = $serializer;
        $this->attachedFileDataProvider = $attachedFileDataProvider;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    public function render(AnswerInterface $answer, array &$attachments = []): string
    {
        try {
            $fields = $this->serializer->unserialize($answer->getResponseJson());
        } catch (\Exception $e) {
            $fields = [];
        }

        $viewModel = $this->fieldsDataProviderFactory->create();
        $viewModel->setFieldsData($this->getFieldsForRendering($fields, $attachments));

        $block = $this->blockFactory->createBlock(
            Template::class,
            [
                'data' => [
                    'view_model' => $viewModel,
                    'template' => $this->emailFieldsTemplate
                ]
            ]
        );

        return $block->toHtml();
    }

    /**
     * @param string[][] $formFields
     * @param array $attachments
     *
     * @return DataObject[]
     */
    private function getFieldsForRendering(array $formFields, array &$attachments): array
    {
        $result = [];

        foreach ($formFields as $field) {
            $value = $field[Submit::VALUE] ?? '';
            $type = $field[Submit::TYPE] ?? '';
            $label = $field[Submit::LABEL] ?? '';

            if ($type === self::TYPE_FILE) {
                if (is_array($value)) {
                    $filteredFiles = array_filter($value);

                    foreach ($filteredFiles as $fileName) {
                        if ($fileName) {
                            $this->addAttachment($attachments, $fileName);
                        }
                    }
                } else {
                    $this->addAttachment($attachments, $value);
                }
            }

            if (is_array($value)) {
                $filteredFiles = array_filter($value);
                $value = implode(', ', $filteredFiles);
            }

            $result[] = $this->dataObjectFactory->create([
                'data' => [
                    'label' => $label,
                    'value' => $value
                ]
            ]);
        }

        return $result;
    }

    /**
     * @param array $attachments
     * @param string|string[] $value
     */
    private function addAttachment(array &$attachments, $value): void
    {
        try {
            $attachments[$value] = $this->attachedFileDataProvider->getContents($value);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\Throwable $e) {
        }
    }
}

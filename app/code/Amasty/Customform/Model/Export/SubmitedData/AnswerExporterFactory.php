<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Export\SubmitedData;

use Amasty\Customform\Api\Answer\AnswerExporterInterface;
use Amasty\Customform\Exceptions\InvalidExportTypeException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class AnswerExporterFactory
{
    public const TYPE_PDF = 'pdf';

    public const RESULT_TYPE = 'result_type';
    public const RENDERER_TYPE = 'renderer_type';
    public const RESULT_NAME_GENERATOR = 'result_name_generator';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $resultRenderersMap;

    /**
     * @var string
     */
    private $answerExporterType;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $answerExporterType
     * @param array $resultRenderersMap
     *
     * @example for resultRenderersMap: [
     *    0 => [
     *        'result_type' => 'pdf',
     *        'renderer_type' => '\Amasty\Customform\Model\Export\SubmitedData\Pdf\PdfResultRenderer',
     *        'result_name_generator' => '\Amasty\Customform\Model\Export\SubmitedData\Pdf\PdfResultNameGenerator'
     *      ]
     *   ]
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        string $answerExporterType = AnswerExporter::class,
        array $resultRenderersMap = []
    ) {
        $this->objectManager = $objectManager;
        $this->resultRenderersMap = $this->parseResultRenderersConfig($resultRenderersMap);
        $this->answerExporterType = $answerExporterType;
    }

    /**
     * @param string $resultType
     * @param array $params
     * @return AnswerExporterInterface
     * @throws InvalidExportTypeException
     */
    public function create(string $resultType, array $params = []): AnswerExporterInterface
    {
        $rendererType = $this->resultRenderersMap[$resultType][self::RENDERER_TYPE] ?? null;
        $nameGeneratorType = $this->resultRenderersMap[$resultType][self::RESULT_NAME_GENERATOR] ?? null;

        if (is_string($rendererType) && is_string($nameGeneratorType)) {
            $renderer = $this->objectManager->create($rendererType);
            $nameGenerator = $this->objectManager->create($nameGeneratorType);

            if (false === $renderer instanceof ResultRendererInterface) {
                throw new InvalidExportTypeException(
                    __('Invalid DI configuration. Result renderer must implements %1', ResultRendererInterface::class)
                );
            }

            if (false === $nameGenerator instanceof ResultNameGeneratorInterface) {
                throw new InvalidExportTypeException(
                    __(
                        'Invalid DI configuration. Result name generator must implements %1',
                        ResultNameGeneratorInterface::class
                    )
                );
            }

            $params = array_merge(['resultRenderer' => $renderer, 'resultNameGenerator' => $nameGenerator], $params);

            return $this->objectManager->create($this->answerExporterType, $params);
        } else {
            throw new InvalidExportTypeException(__(
                'DI configuration for result type %1 does not exists or is invalid',
                $resultType
            ));
        }
    }

    /**
     * @param string[][] $resultRenderersMap
     * @return string[]
     */
    private function parseResultRenderersConfig(array $resultRenderersMap): array
    {
        return array_combine(
            array_column($resultRenderersMap, self::RESULT_TYPE),
            $resultRenderersMap
        );
    }
}

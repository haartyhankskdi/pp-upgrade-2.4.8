<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Api\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Export\ExportResultInterface;

/**
 * @api
 */
interface AnswerExporterInterface
{
    /**
     * @param AnswerInterface $answer
     * @return ExportResultInterface
     */
    public function export(AnswerInterface $answer): ExportResultInterface;

    /**
     * @param AnswerInterface[] $answerSource
     * @return ExportResultInterface[]
     */
    public function exportMultiple(iterable $answerSource): iterable;
}

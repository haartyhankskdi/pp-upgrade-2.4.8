<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Export\SubmitedData\Pdf;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Export\SubmitedData\Pdf\Generators\PdfGeneratorFactory;
use Amasty\Customform\Model\Export\SubmitedData\ResultRendererInterface;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\DocumentFactory;
use Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\ExternalCssProvider;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;

class PdfResultRenderer implements ResultRendererInterface
{
    public const DEFAULT_TEMPLATE = 'Amasty_Customform::export/pdf/submitted_data/document.phtml';

    /**
     * @var PdfGeneratorFactory
     */
    private $pdfGeneratorFactory;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var DocumentFactory
     */
    private $documentViewModelFactory;

    /**
     * @var string
     */
    private $pdfTemplate;

    /**
     * @var ExternalCssProvider
     */
    private $externalCssProvider;

    public function __construct(
        PdfGeneratorFactory $pdfGeneratorFactory,
        BlockFactory $blockFactory,
        DocumentFactory $documentViewModelFactory,
        ExternalCssProvider $externalCssProvider,
        $pdfTemplate = self::DEFAULT_TEMPLATE
    ) {
        $this->pdfGeneratorFactory = $pdfGeneratorFactory;
        $this->blockFactory = $blockFactory;
        $this->documentViewModelFactory = $documentViewModelFactory;
        $this->pdfTemplate = $pdfTemplate;
        $this->externalCssProvider = $externalCssProvider;
    }

    public function render(AnswerInterface $answer): string
    {
        $pdfGenerator = $this->pdfGeneratorFactory->create();
        $pdfGenerator->setHtml($this->getAnsweredDataHtml($answer));
        $pdfGenerator->setCss($this->externalCssProvider->getPdfStyles());

        return $pdfGenerator->render();
    }

    private function getAnsweredDataHtml(AnswerInterface $answer): string
    {
        $viewModel = $this->documentViewModelFactory->create();
        $viewModel->setAnswer($answer);
        /** @var Template $block **/
        $block = $this->blockFactory->createBlock(
            Template::class,
            ['data' => ['view_model' => $viewModel]]
        );
        $block->setTemplate($this->pdfTemplate);

        return $block->toHtml();
    }
}

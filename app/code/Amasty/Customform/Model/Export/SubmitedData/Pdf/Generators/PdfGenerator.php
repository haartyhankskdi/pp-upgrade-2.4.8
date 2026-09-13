<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Export\SubmitedData\Pdf\Generators;

/**
 * @description Wrapper for PDF generator library
 */
class PdfGenerator implements PdfGeneratorInterface
{
    public const RESULT_DPI = 150;

    /**
     * @var \Dompdf\Dompdf
     */
    private $domPdf;

    /**
     * @var string
     */
    private $html = '';

    /**
     * @var string
     */
    private $css = '';

    /**
     * @var int
     */
    private $resultDpi;

    public function __construct(
        $domPdf,
        $resultDpi = self::RESULT_DPI
    ) {
        $this->domPdf = $domPdf;
        $this->resultDpi = $resultDpi;
    }

    public function setCss(string $cssString): void
    {
        $this->css = $cssString;
    }

    public function setHtml(string $html, ?string $encoding = null): void
    {
        $this->html = $html;
    }

    public function render(): string
    {
        $domPdf = $this->getConfiguredRenderer();
        $domPdf->loadHtml($this->html);
        $domPdf->getCss()->load_css($this->css);
        $domPdf->render();

        return $domPdf->output();
    }

    /**
     * @return \Dompdf\Dompdf
     */
    private function getConfiguredRenderer()
    {
        $domPdf = $this->domPdf;
        $options = $domPdf->getOptions();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(true);
        $options->setLogOutputFile(false);
        $options->setDpi($this->resultDpi);
        $options->setDefaultPaperOrientation('portrait');
        $options->setDefaultPaperSize('a4');
        $options->setIsPhpEnabled(true);
        $httpContextConfig = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed'=> true
            ]
        ];
        //phpcs:ignore
        $domPdf->setHttpContext(stream_context_create($httpContextConfig));

        return $domPdf;
    }
}

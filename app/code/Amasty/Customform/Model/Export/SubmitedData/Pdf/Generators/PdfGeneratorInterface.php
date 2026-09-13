<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Export\SubmitedData\Pdf\Generators;

interface PdfGeneratorInterface
{
    /**
     * @param string $cssString
     */
    public function setCss(string $cssString): void;

    /**
     * @param string $html
     * @param string|null $encoding
     */
    public function setHtml(string $html, ?string $encoding = null): void;

    /**
     * @return string
     */
    public function render(): string;
}

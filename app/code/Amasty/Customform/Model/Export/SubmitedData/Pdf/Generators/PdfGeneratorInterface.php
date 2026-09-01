<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

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

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields;

use Magento\Framework\App\Area;
use Magento\Framework\View\Element\Template\File\Resolver;

class TemplateGenerator
{
    public const FIELD_TEMPLATE_PATH = 'Amasty_Customform::export/pdf/submitted_data/fields/%s.phtml';

    /**
     * @var string
     */
    private $defaultTemplate;

    /**
     * @var Resolver
     */
    private $templateResolver;

    public function __construct(
        Resolver $templateResolver,
        string $defaultTemplate = 'Amasty_Customform::export/pdf/submitted_data/fields/default.phtml'
    ) {
        $this->defaultTemplate = $defaultTemplate;
        $this->templateResolver = $templateResolver;
    }

    public function generate(string $type): string
    {
        $templateFileName = sprintf(self::FIELD_TEMPLATE_PATH, $type);
        $templateFile = $this->templateResolver->getTemplateFileName(
            $templateFileName,
            ['area' => Area::AREA_FRONTEND]
        );

        return $templateFile ? $templateFileName : $this->defaultTemplate;
    }
}

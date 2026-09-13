<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Document;

class IsCanRenderFieldComposite implements IsCanRenderFieldInterface
{
    /**
     * @var IsCanRenderFieldInterface[]
     */
    private $validators;

    public function __construct(
        array $validators = []
    ) {
        $this->validators = $validators;
    }

    public function isCanRender(array $fieldConfig): bool
    {
        $result = true;

        foreach ($this->validators as $validator) {
            if ($validator instanceof IsCanRenderFieldInterface && !$validator->isCanRender($fieldConfig)) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}

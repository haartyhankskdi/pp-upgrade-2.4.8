<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Validation;

class ValidationComposite implements ValidationInterface
{
    /**
     * @var ValidationInterface[]
     */
    private $validationProcessors;

    public function __construct(
        array $validationProcessors = []
    ) {
        $this->validationProcessors = $validationProcessors;
    }

    public function validate(array $formData): void
    {
        foreach ($this->validationProcessors as $validationProcessor) {
            $validationProcessor->validate($formData);
        }
    }
}

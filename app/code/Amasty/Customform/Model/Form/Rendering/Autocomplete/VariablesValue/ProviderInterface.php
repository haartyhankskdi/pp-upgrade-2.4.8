<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue;

interface ProviderInterface
{
    /**
     * @param string $variableName
     *
     * @return bool
     */
    public function isCanRetrieve(string $variableName): bool;

    /**
     * @param string $variableName
     *
     * @return string
     */
    public function getValue(string $variableName): string;
}

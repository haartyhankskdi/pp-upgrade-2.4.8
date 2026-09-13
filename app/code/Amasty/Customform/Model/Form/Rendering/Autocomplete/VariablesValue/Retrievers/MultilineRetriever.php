<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers;

use Magento\Eav\Api\Data\AttributeInterface;

class MultilineRetriever implements RetrieverInterface
{
    /**
     * Retrieve comma separated multiline attribute value text
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @return string
     */
    public function retrieve(AttributeInterface $attribute, string $value): string
    {
        $values = array_map('trim', explode(PHP_EOL, $value));

        return join(', ', $values);
    }
}

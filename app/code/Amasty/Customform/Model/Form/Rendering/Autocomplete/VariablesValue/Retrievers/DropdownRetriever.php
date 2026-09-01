<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers;

use Magento\Eav\Api\Data\AttributeInterface;

class DropdownRetriever implements RetrieverInterface
{
    /**
     * Retrieve formatted attribute option value text
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @return string
     */
    public function retrieve(AttributeInterface $attribute, string $value): string
    {
        $result = '';

        foreach ($attribute->getOptions() as $option) {
            if ($option->getValue() == $value) {
                $result = (string) $option->getLabel();
                break;
            }
        }

        return $result;
    }
}

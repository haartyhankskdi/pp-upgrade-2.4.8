<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers;

use Magento\Eav\Api\Data\AttributeInterface;

class MultiselectRetriever implements RetrieverInterface
{
    /**
     * @var DropdownRetriever
     */
    private $dropdownRetriever;

    public function __construct(
        DropdownRetriever $dropdownRetriever
    ) {
        $this->dropdownRetriever = $dropdownRetriever;
    }

    /**
     * Retrieve comma separated attribute option values text
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @return string
     */
    public function retrieve(AttributeInterface $attribute, string $value): string
    {
        $values = array_map('trim', explode(',', $value));
        $labels = array_map(function (string $singleValue) use ($attribute): string {
            return $this->dropdownRetriever->retrieve($attribute, $singleValue);
        }, $values);

        return join(', ', $labels);
    }
}

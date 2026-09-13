<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers;

use Magento\Eav\Api\Data\AttributeInterface;

class CompositeRetriever implements RetrieverInterface
{
    /**
     * @var RetrieverInterface[]
     */
    private $retrieverPool;

    /**
     * @var DummyRetriever
     */
    private $dummyRetriever;

    public function __construct(
        DummyRetriever $dummyRetriever,
        array $retrieversPool = []
    ) {
        $this->retrieverPool = $retrieversPool;
        $this->dummyRetriever = $dummyRetriever;
    }

    /**
     * Retreive attribute value text
     *
     * @param AttributeInterface $attribute
     * @param string $value
     * @return string
     */
    public function retrieve(AttributeInterface $attribute, string $value): string
    {
        $retriever = $this->getRetriever($attribute);

        return $retriever->retrieve($attribute, $value);
    }

    private function getRetriever(AttributeInterface $attribute): RetrieverInterface
    {
        return $this->retrieverPool[$attribute->getFrontendInput()] ?? $this->dummyRetriever;
    }
}

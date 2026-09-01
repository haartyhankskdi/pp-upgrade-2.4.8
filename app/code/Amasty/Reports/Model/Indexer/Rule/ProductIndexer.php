<?php

namespace Amasty\Reports\Model\Indexer\Rule;

class ProductIndexer extends AbstractIndexer
{
    /**
     * @inheritdoc
     */
    protected function cleanList($ids)
    {
        $this->getIndexResource()->cleanByProductIds($ids);
    }

    /**
     * @inheritdoc
     */
    protected function setProductsFilter($rule, $productIds)
    {
        $rule->setProductsFilter($productIds);
    }

    /**
     * @inheritdoc
     */
    protected function getProcessedRules($ids = [])
    {
        return $this->getRules()->getItems();
    }
}

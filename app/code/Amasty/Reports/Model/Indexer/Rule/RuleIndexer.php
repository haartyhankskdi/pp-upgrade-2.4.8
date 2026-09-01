<?php

namespace Amasty\Reports\Model\Indexer\Rule;

class RuleIndexer extends AbstractIndexer
{
    /**
     * @inheritdoc
     */
    protected function cleanList($ids)
    {
        $this->getIndexResource()->cleanByRuleIds($ids);
    }

    /**
     * @inheritdoc
     */
    protected function setProductsFilter($rule, $productIds)
    {
        $rule->setProductsFilter(null);
    }

    /**
     * @inheritdoc
     */
    protected function getProcessedRules($ids = [])
    {
        return $this->getRules($ids)->getItems();
    }
}

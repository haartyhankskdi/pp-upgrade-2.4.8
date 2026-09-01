<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SalesRuleList implements OptionSourceInterface
{
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     */
    public function __construct(\Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory)
    {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $collection = $this->ruleCollectionFactory->create();
        $options    = [];
        foreach ($collection as $rule) {
            $options[] = [
                'label' => $rule->getName(),
                'value' => $rule->getId()
            ];
        }
        $this->options = $options;

        return $this->options;
    }
}

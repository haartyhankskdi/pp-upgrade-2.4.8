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

class PopupList implements OptionSourceInterface
{
    /**
     * @var \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $collectionFactory
     */
    public function __construct(\Magezon\PopupBuilder\Model\ResourceModel\Popup\CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $options = [];
        foreach ($collection as $popup) {
            $options[] = [
                'label' => $popup->getName(),
                'value' => $popup->getId()
            ];
        }
        return $options;
    }
}

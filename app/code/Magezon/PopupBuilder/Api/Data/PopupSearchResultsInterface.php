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

namespace Magezon\PopupBuilder\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface PopupSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get popup list.
     *
     * @return \Magezon\PopupBuilder\Api\Data\PopupInterface[]
     */
    public function getItems();

    /**
     * Set popup list.
     *
     * @param \Magezon\PopupBuilder\Api\Data\PopupInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
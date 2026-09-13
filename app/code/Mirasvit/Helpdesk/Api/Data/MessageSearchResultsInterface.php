<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\Helpdesk\Api\Data;

/**
 * Interface for message search results.
 */
interface MessageSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get messages list.
     *
     * @return \Mirasvit\Helpdesk\Api\Data\MessageItemInterface[]
     */
    public function getItems();

    /**
     * Set messages list.
     *
     * @param \Mirasvit\Helpdesk\Api\Data\MessageItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

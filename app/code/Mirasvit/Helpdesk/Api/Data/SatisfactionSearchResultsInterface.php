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
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\Helpdesk\Api\Data;

/**
 * Interface for satisfaction search results.
 */
interface SatisfactionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get tickets list.
     *
     * @return \Mirasvit\Helpdesk\Model\Satisfaction[]
     */
    public function getItems();

    /**
     * Set satisfactions list.
     *
     * @param array $items Array of \Mirasvit\Helpdesk\Model\Satisfaction[]
     * @return $this
     */
    public function setItems(array $items);
}

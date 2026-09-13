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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Report\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface ReportApiSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Mirasvit\Report\Api\Data\ReportApiInterface[]
     */
    public function getItems();

    /**
     * @param \Mirasvit\Report\Api\Data\ReportApiInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

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

namespace Mirasvit\Report\Model\Email;

use Magento\Framework\DataObject;
use Mirasvit\Report\Api\Data\Email\BlockInterface;

class Block extends DataObject implements BlockInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return (string)$this->getData('identifier');
    }

    /**
     * @param string $identifier
     * @return \Mirasvit\Report\Api\Data\Email\BlockInterface
     */
    public function setIdentifier(string $identifier): BlockInterface
    {
        return $this->setData('identifier', $identifier);
    }

    /**
     * @return string
     */
    public function getTimeRange(): string
    {
        return (string)$this->getData('timeRange');
    }

    /**
     * @param string $timeRange
     * @return \Mirasvit\Report\Api\Data\Email\BlockInterface
     */
    public function setTimeRange(string $timeRange): BlockInterface
    {
        return $this->setData('timeRange', $timeRange);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return (int)$this->getData('limit');
    }

    /**
     * @param int $limit
     * @return \Mirasvit\Report\Api\Data\Email\BlockInterface
     */
    public function setLimit(int $limit): BlockInterface
    {
        return $this->setData('limit', $limit);
    }
}

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
 * @package   mirasvit/module-report-api
 * @version   1.0.95
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\ReportApi\Processor;

use Magento\Framework\Api\AbstractSimpleObject;
use Mirasvit\ReportApi\Api\Processor\SortOrderInterface;

class RequestSortOrder extends AbstractSimpleObject implements SortOrderInterface, \JsonSerializable
{
    const COLUMN    = 'column';
    const DIRECTION = 'direction';

    private $serializer;

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($data);
    }

    /**
     * @param string $column
     * @return $this
     */
    public function setColumn(string $column)
    {
        return $this->setData(self::COLUMN, $column);
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return (string)$this->_get(self::COLUMN);
    }

    /**
     * @param string $direction
     * @return $this
     */
    public function setDirection(string $direction)
    {
        return $this->setData(self::DIRECTION, $direction);
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return (string)$this->_get(self::DIRECTION);
    }

    public function jsonSerialize(): array
    {
        return $this->__toArray();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->serializer->serialize($this->__toArray());
    }
}

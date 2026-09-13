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
use Mirasvit\ReportApi\Api\Processor\FilterInterface;

class RequestFilter extends AbstractSimpleObject implements FilterInterface, \JsonSerializable
{
    const COLUMN         = 'column';
    const VALUE          = 'value';
    const CONDITION_TYPE = 'condition_type';
    const GROUP          = 'group';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var array
     */
    private static $keyMap = [
        'conditionType' => self::CONDITION_TYPE,
    ];

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        array $data = []
    ) {
        $this->serializer = $serializer;

        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[self::$keyMap[$key] ?? $key] = $value;
        }

        parent::__construct($normalized);
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
     * @param string|string[] $value
     * @return $this
     */
    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        return $this->_get(self::VALUE);
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setConditionType(string $type)
    {
        return $this->setData(self::CONDITION_TYPE, $type);
    }

    /**
     * @return string
     */
    public function getConditionType(): string
    {
        return (string)$this->_get(self::CONDITION_TYPE);
    }

    /**
     * @param string $group
     * @return $this
     */
    public function setGroup(string $group)
    {
        return $this->setData(self::GROUP, $group);
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return (string)$this->_get(self::GROUP);
    }

    public function jsonSerialize(): array
    {
        return [
            'column'        => $this->getColumn(),
            'conditionType' => $this->getConditionType(),
            'value'         => $this->getValue(),
            'group'         => $this->getGroup(),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->serializer->serialize($this->__toArray());
    }
}

<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Di;

class WrapperFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManagerInterface;

    /**
     * @var string
     */
    private $name;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        $name = ''
    ) {
        $this->objectManagerInterface = $objectManagerInterface;
        $this->name = $name;
    }

    /**
     * @param array $arguments
     * @return false|mixed
     */
    public function create(array $arguments)
    {
        $object = false;
        if ($this->name && class_exists($this->name)) {
            $object = $this->objectManagerInterface->create($this->name, $arguments);
        }

        return $object;
    }
}

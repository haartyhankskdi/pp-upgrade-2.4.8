<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Di;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class Wrapper implements ArgumentInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManagerInterface;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $object;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        $name = ''
    ) {
        $this->objectManagerInterface = $objectManagerInterface;
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $result = false;
        if ($this->name && class_exists($this->name)) {
            $this->prepareObject();
            // @codingStandardsIgnoreLine
            $result = call_user_func_array([$this->object, $name], $arguments);
        }

        return $result;
    }

    private function prepareObject(): void
    {
        if (!$this->object) {
            $this->object = $this->objectManagerInterface->create($this->name);
        }
    }
}

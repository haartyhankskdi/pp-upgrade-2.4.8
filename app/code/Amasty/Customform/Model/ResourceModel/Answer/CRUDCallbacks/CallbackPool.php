<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\ResourceModel\Answer\CRUDCallbacks;

class CallbackPool implements \IteratorAggregate
{
    /**
     * @var CallbackInterface[]
     */
    private $callbacks;

    public function __construct(
        $callbacks = []
    ) {
        $this->callbacks = $callbacks;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->callbacks);
    }
}

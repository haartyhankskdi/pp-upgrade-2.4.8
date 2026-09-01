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



namespace Mirasvit\Helpdesk\Service\Email;

use Magento\Framework\DataObject;

class VariableObject extends DataObject
{
    /**
     * @var mixed
     */
    private $coreObject = null;

    /**
     * @param mixed $object
     *
     * @return $this
     */
    public function setCoreObject($object)
    {
        $this->coreObject = $object;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCoreObject()
    {
        return $this->coreObject;
    }

    /**
     * @inheritDoc
     */
    public function __call($method, $args)
    {
        return $this->getCoreObject()->$method(...$args);
    }

    /**
     * @inheritDoc
     */
    public function getData($key = '', $index = null)
    {
        $methodName = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

        if (method_exists($this->getCoreObject(), $methodName)) {
            return $this->getCoreObject()->$methodName();
        } else {
            return $this->getCoreObject()->getData($key, $index);
        }
    }
}

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



namespace Mirasvit\Helpdesk\Model\Data;

use Magento\Framework\DataObject;
use Mirasvit\Helpdesk\Api\Data\AttachmentBodyInterface;

class AttachmentBody extends DataObject implements AttachmentBodyInterface
{
    /**
     * @inheritDoc
     */
    public function getContent()
    {
        return $this->getData('content');
    }

    /**
     * @inheritDoc
     */
    public function getEncoding()
    {
        return $this->getData('encoding');
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getData('name');
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData('type');
    }
}

<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model;

class RelatedPost extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magefan\BlogPlus\Model\ResourceModel\RelatedPost::class);
    }
}

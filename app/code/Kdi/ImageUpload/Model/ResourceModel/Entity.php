<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\ImageUpload\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Entity extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('kdi_imageupload_entity', 'entity_id');
    }
}


<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\Testimonials\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Testimonials extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('kdi_testimonials', 'testimonials_id');
    }
}


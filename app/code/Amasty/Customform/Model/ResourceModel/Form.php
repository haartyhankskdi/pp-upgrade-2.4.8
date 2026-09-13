<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\ResourceModel;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Form extends AbstractDb
{
    public const TABLE = 'amasty_customform_form';

    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, FormInterface::FORM_ID);
    }
}

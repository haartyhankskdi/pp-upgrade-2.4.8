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



namespace Mirasvit\Helpdesk\Model\ResourceModel;

class CustomerNote extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var bool
     */
    protected $_useIsObjectNew = true;

    /**
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('mst_helpdesk_customer', 'customer_id');
    }
}

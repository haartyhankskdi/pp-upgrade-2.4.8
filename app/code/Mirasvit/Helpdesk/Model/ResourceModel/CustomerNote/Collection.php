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



namespace Mirasvit\Helpdesk\Model\ResourceModel\CustomerNote;

/**
 * @method \Mirasvit\Helpdesk\Model\Status getFirstItem()
 * @method \Mirasvit\Helpdesk\Model\Status getLastItem()
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Status\Collection|\Mirasvit\Helpdesk\Model\Status[] addFieldToFilter
 * @method \Mirasvit\Helpdesk\Model\ResourceModel\Status\Collection|\Mirasvit\Helpdesk\Model\Status[] setOrder
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'customer_id';//@codingStandardsIgnoreLine

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\Helpdesk\Model\CustomerNote', 'Mirasvit\Helpdesk\Model\ResourceModel\CustomerNote');
    }
}

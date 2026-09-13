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
    /**
     * Remove the agent note held about a deleted customer.
     *
     * Deleted, not detached: this table is nothing but customer_id (its primary key) and
     * customer_note, so with the customer gone there is no row left worth having - and what it holds
     * is an agent's private remark ABOUT that person, which is exactly the kind of thing that should
     * not outlive them.
     *
     * @return int the number of notes removed
     */
    public function deleteByCustomerId(int $customerId): int
    {
        if ($customerId <= 0) {
            return 0;
        }

        $connection = $this->getConnection();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */

        return $connection->delete($this->getMainTable(), ['customer_id = ?' => $customerId]);
    }
}

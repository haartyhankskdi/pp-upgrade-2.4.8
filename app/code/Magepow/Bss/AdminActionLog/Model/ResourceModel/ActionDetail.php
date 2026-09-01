<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ActionDetail extends AbstractDb
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bss_admin_action_detail_log', 'id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $actionDetail
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $actionDetail)
    {
        $actionDetail->setData('old_value', json_encode($actionDetail->getOldValue(), JSON_FORCE_OBJECT));
        $actionDetail->setData('new_value', json_encode($actionDetail->getNewValue(), JSON_FORCE_OBJECT));
        return $this;
    }
}

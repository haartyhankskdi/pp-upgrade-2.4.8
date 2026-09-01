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



namespace Mirasvit\Helpdesk\Ui\Component\DataProvider;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TicketCustomerGridDataProvider extends TicketGridDataProvider
{
    /**
     * {@inheritdoc}
     */
    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == 'id' || $filter->getField() == 'main_table.id') {
            $filter->setField('main_table.customer_id');
        }

        parent::addFilter($filter);
    }
}

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


namespace Mirasvit\Helpdesk\Ui\Form\Gateway;

class DepartmentOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory
     */
    private $departmentFactory;

    /**
     * DepartmentOptionsProvider constructor.
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $departmentFactory
     */
    public function __construct(\Mirasvit\Helpdesk\Model\ResourceModel\Department\CollectionFactory $departmentFactory)
    {
        $this->departmentFactory = $departmentFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->departmentFactory->create()->toOptionArray(false);
    }
}

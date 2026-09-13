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


namespace Mirasvit\Helpdesk\Ui\Form\Permission;

use Mirasvit\Helpdesk\Model\ResourceModel\Permission\CollectionFactory;
use Mirasvit\Helpdesk\Model\ResourceModel\Permission;

class DataProvider extends \Mirasvit\Helpdesk\Ui\Form\DataProvider
{
    /**
     * @var Permission
     */
    private $permissionResource;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @param Permission $permissionResource
     * @param CollectionFactory $collectionFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        Permission $permissionResource,
        CollectionFactory $collectionFactory,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->permissionResource = $permissionResource;
        $this->collection         = $collectionFactory->create();

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [];
        /** @var \Mirasvit\Helpdesk\Model\Permission $permission */
        foreach ($this->getCollection() as $permission) {
            $this->permissionResource->afterLoad($permission);

            $data[$permission->getId()] = $permission->getData();
            if (!empty($data[$permission->getId()]['department_ids'])) { // we need this
                foreach ($data[$permission->getId()]['department_ids'] as $k => $id) {
                    $data[$permission->getId()]['department_ids'][$k] = $id.'';
                }
            }
        }

        return $data;
    }
}

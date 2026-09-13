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


namespace Mirasvit\Helpdesk\Api\Data;

interface DepartmentInterface
{
    const TABLE_NAME = 'mst_helpdesk_department';

    const ID = 'department_id';

    const KEY_NAME               = 'name';
    const KEY_NOTIFICATION_EMAIL = 'notification_email';
    const KEY_SORT_ORDER         = 'sort_order';
    const KEY_IS_ACTIVE          = 'is_active';

    /**
     * @return int
     */
    public function getDepartmentId();

    /**
     * @param int $departmentId
     * @return $this
     */
    public function setDepartmentId($departmentId);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getNotificationEmail();

    /**
     * @param string $notificationEmail
     * @return $this
     */
    public function setNotificationEmail($notificationEmail);

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder);

    /**
     * @return int
     */
    public function getIsActive();

    /**
     * @param int $isActive
     * @return $this
     */
    public function setIsActive($isActive);
}
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

interface StatusInterface
{
    const OPEN        = 1;
    const IN_PROGRESS = 2;
    const CLOSED      = 3;
    const TABLE_NAME  = 'mst_helpdesk_status';

    const ID = 'status_id';

    const KEY_NAME       = 'name';
    const KEY_CODE       = 'code';
    const KEY_COLOR      = 'color';
    const KEY_SORT_ORDER = 'sort_order';

    /**
     * @return int
     */
    public function getStatusId();

    /**
     * @param int $statusId
     * @return $this
     */
    public function setStatusId($statusId);

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
    public function getCode();

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code);

    /**
     * @return string
     */
    public function getColor();

    /**
     * @param string $color
     * @return $this
     */
    public function setColor($color);

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder);
}
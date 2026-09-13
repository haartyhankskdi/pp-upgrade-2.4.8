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

interface FieldInterface
{
    const TABLE_NAME = 'mst_helpdesk_field';

    const ID = 'field_id';

    const KEY_NAME        = 'name';
    const KEY_CODE        = 'code';
    const KEY_DESCRIPTION = 'description';
    const KEY_VALUES      = 'values';
    const KEY_TYPE        = 'type';
    const KEY_IS_ACTIVE   = 'is_active';
    const KEY_SORT_ORDER  = 'sort_order';

    /**
     * @return int
     */
    public function getFieldId();

    /**
     * @param int $fieldId
     * @return $this
     */
    public function setFieldId($fieldId);

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
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return string
     */
    public function getValues();

    /**
     * @param string $values
     * @return $this
     */
    public function setValues($values);

    /**
     * @return int
     */
    public function getIsActive();

    /**
     * @param int $isActive
     * @return $this
     */
    public function setIsActive($isActive);

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
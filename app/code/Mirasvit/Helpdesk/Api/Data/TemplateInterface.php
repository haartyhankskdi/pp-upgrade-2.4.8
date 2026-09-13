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

interface TemplateInterface
{
    const TABLE_NAME = 'mst_helpdesk_template';

    const ID             = 'template_id';
    const KEY_NAME       = 'name';
    const KEY_TEMPLATE   = 'template';
    const KEY_IS_ACTIVE  = 'is_active';
    const KEY_STORE_IDS  = 'store_ids';
    const KEY_CREATED_AT = 'created_at';
    const KEY_UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getTemplateId();

    /**
     * @param int $templateId
     * @return $this
     */
    public function setTemplateId($templateId);

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
    public function getTemplate();

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template);

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
     * @return int[]
     */
    public function getStoreIds();

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}

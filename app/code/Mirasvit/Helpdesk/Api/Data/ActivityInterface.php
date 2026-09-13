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

interface ActivityInterface
{
    const TABLE_NAME = 'mst_helpdeskext_activity';

    const ID          = 'activity_id';
    const EXTERNAL_ID = 'external_id';
    const KIND        = 'mst_kind';
    const TIMESTAMP   = 'mst_timestamp';
    const USER_ID     = 'user_id';
    const TITLE       = 'mst_title';
    const DESCRIPTION = 'mst_description';
    const URL         = 'mst_url';
    const PAYLOAD     = 'mst_payload';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getExternalId();

    /**
     * @param string $value
     * @return $this
     */
    public function setExternalId($value);

    /**
     * @return string
     */
    public function getKind();

    /**
     * @param string $value
     * @return $this
     */
    public function setKind($value);

    /**
     * @return string
     */
    public function getTimestamp();

    /**
     * @param string $value
     * @return $this
     */
    public function setTimestamp($value);

    /**
     * @return int
     */
    public function getUserId();

    /**
     * @param string $value
     * @return $this
     */
    public function setUserId($value);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $value
     * @return $this
     */
    public function setTitle($value);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $value
     * @return $this
     */
    public function setDescription($value);

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @param string $value
     * @return $this
     */
    public function setUrl($value);

    /**
     * @return mixed[]
     */
    public function getPayload();

    /**
     * @param string $value
     * @return $this
     */
    public function setPayload($value);
}

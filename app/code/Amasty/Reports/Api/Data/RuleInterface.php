<?php

namespace Amasty\Reports\Api\Data;

interface RuleInterface
{
    public const TABLE_NAME = 'amasty_reports_rule';

    public const PERSIST_NAME = 'amasty_report_rule';

    /**#@+
     * Constants defined for keys of data array
     */
    public const ENTITY_ID = 'entity_id';

    public const TITLE = 'title';

    public const STATUS = 'status';

    public const UPDATED_AT = 'updated_at';
    
    public const CONDITIONS = 'conditions_serialized';

    public const PIN = 'pin';
    /**#@-*/

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setEntityId($entityId);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $title
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setTitle($title);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setStatus($status);

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string|null $updatedAt
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string|null
     */
    public function getSerializedConditions();

    /**
     * @param string|null $conditions
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setSerializedConditions($conditions);

    /**
     * @return bool
     */
    public function isConditionEmpty();

    /**
     * @return int
     */
    public function getPin();

    /**
     * @param int $pin
     *
     * @return \Amasty\Reports\Api\Data\RuleInterface
     */
    public function setPin($pin);
}

<?php

namespace Amasty\Reports\Model\ResourceModel;

use Amasty\Reports\Api\Data\RuleInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Rule extends AbstractDb
{
    /**
     * Initialize table and PK name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(RuleInterface::TABLE_NAME, RuleInterface::ENTITY_ID);
    }

    /**
     * @param int $status
     * @param int|null $ruleId
     */
    public function updateStatus($status, $ruleId)
    {
        $this->updateField(RuleInterface::STATUS, $status, $ruleId);
    }

    /**
     * @param string $date
     * @param int|null $ruleId
     */
    public function updateLastUpdated($date, $ruleId)
    {
        $this->updateField(RuleInterface::UPDATED_AT, $date, $ruleId);
    }

    /**
     * @param string $field
     * @param string $value
     * @param int|null $ruleId
     */
    private function updateField($field, $value, $ruleId)
    {
        $where = [];
        if ($ruleId) {
            $where[RuleInterface::ENTITY_ID . ' = ?'] = $ruleId;
        }
        $this->getConnection()->update(
            $this->getMainTable(),
            [$field => $value],
            $where
        );
    }
}

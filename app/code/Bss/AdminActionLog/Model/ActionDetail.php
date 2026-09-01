<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\AdminActionLog\Model;

use Magento\Framework\Model\AbstractModel;

class ActionDetail extends AbstractModel
{
    /**
     * @var null
     */
    protected $difference = null;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bss\AdminActionLog\Model\ResourceModel\ActionDetail');
    }

    /**
     * HasDifference
     *
     * @return bool
     */
    public function hasDifference()
    {
        $difference = $this->calculateDifference();
        return !empty($difference);
    }

    /**
     * Calculate Difference
     *
     * @return array|null
     */
    protected function calculateDifference()
    {
        if ($this->difference === null) {
            $updatedParams = $newParams = $sameParams = $difference = [];
            $oldData = $this->getOldValue();
            $newData = $this->getNewValue();

            if (!is_array($oldData)) {
                $oldData = [];
            }
            if (!is_array($newData)) {
                $newData = [];
            }

            if (!$oldData && $newData) {
                $oldData = ['_create' => true];
                $difference = $newData;
            } elseif ($oldData && !$newData) {
                $newData = ['_delete' => true];
                $difference = $oldData;
            } elseif ($oldData && $newData) {
                $newParams = array_diff_key($newData, $oldData);
                $sameParams = array_intersect_key($oldData, $newData);
                foreach ($sameParams as $key => $value) {
                    if ($oldData[$key] != $newData[$key]) {
                        $updatedParams[$key] = $newData[$key];
                    }
                }
                $oldData = array_intersect_key($oldData, $updatedParams);
                $difference = $newData = array_merge($updatedParams, $newParams);
                if ($difference && !$updatedParams) {
                    $oldData = ['_no_change' => true];
                }
            }

            $this->setOldValue($oldData);
            $this->setNewValue($newData);

            $this->difference = $difference;
        }
        return $this->difference;
    }

    /**
     * Clean up data
     *
     * @return void
     */
    public function cleanupData()
    {
        $this->setOldValue($this->cleanData($this->getOldValue()));
        $this->setNewValue($this->cleanData($this->getNewValue()));
    }

    /**
     * Clean data
     *
     * @param $data
     * @return array
     */
    protected function cleanData($data)
    {
        if (!$data || !is_array($data)) {
            return [];
        }
        $skipFields = ['created_at', 'updated_at', 'new_password', 'password', 'password_hash', 'password_confirmation'];
        $clearedData = [];

        foreach ($data as $key => $value) {
            $value = $this->covertValue($key, $value);
            if (!in_array(
                $key,
                $skipFields
            ) && !is_array(
                $value
            ) && !is_object(
                $value
                )
            ) {
                $clearedData[$key] = $value;
            }
        }
        return $clearedData;
    }

    /**
     * Convert value
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function covertValue($key, $value)
    {
        if ($key == 'category_ids'
            || $key == 'website_ids'
            || $key == 'rating_codes'
            || $key == 'stores') {
            sort($value);
            if (is_array($value)) {
                return implode(',', $value);
            }
        }
        return $value;
    }
}

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
 * @copyright  Copyright (c) 2017-2023 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Plugin\Model\Service;

class CouponManagementService
{
    /**
     * @var \Bss\AdminActionLog\Model\Log
     */
    protected $log;

    /**
     * @var \Bss\AdminActionLog\Model\Config\Source\ActionInfo
     */
    protected $actionInfo;

    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $rule;

    /**
     * Construct.
     *
     * @param \Bss\AdminActionLog\Model\Log $log
     * @param \Bss\AdminActionLog\Model\Config\Source\ActionInfo $actionInfo
     * @param \Magento\SalesRule\Model\Rule $rule
     */
    public function __construct(
        \Bss\AdminActionLog\Model\Log $log,
        \Bss\AdminActionLog\Model\Config\Source\ActionInfo $actionInfo,
        \Magento\SalesRule\Model\Rule $rule
    ) {
        $this->log = $log;
        $this->actionInfo = $actionInfo;
        $this->rule = $rule;
    }

    /**
     * Add log after generate list coupon code success.
     *
     * @param \Magento\SalesRule\Model\Service\CouponManagementService $subject
     * @param array $result
     * @param \Magento\SalesRule\Api\Data\CouponGenerationSpecInterface $couponSpec
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGenerate($subject, $result, $couponSpec)
    {
        if ($result == []) {
            return $result;
        }

        $rule = $this->rule->load($couponSpec->getRuleId());
        if ($rule && $rule->getUseAutoGeneration()) {
            $action = $this->actionInfo->toArray()['queue:consumers:start'];
            $log = [
                'group_action' => $action['label'],
                'group_name' => $action['group_name'],
                'info' => $rule->getName(),
                'action_type' => $action['action'],
                'action_name' => $action['controller_action'],
                'result' => '1', // empty error
                'store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID // default
            ];

            $index = 0;
            $logDetail[0]['source_data'] = $action['expected_models'];
            foreach ($result as $code) {
                ++$index;
                $logDetail[0]['old_value']['coupon_code_' . $index] = '';
                $logDetail[0]['new_value']['coupon_code_' . $index] = (string)$code;
            }

            $this->log->logCustomAction($log, $logDetail);
        }

        return $result;
    }
}

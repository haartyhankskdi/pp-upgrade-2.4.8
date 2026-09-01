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
namespace Bss\AdminActionLog\Plugin\Controller\Adminhtml\Promo\Quote;

class CouponsMassDelete
{
    /**
     * @var null|int
     */
    protected $ruleIdBefore = null;

    /**
     * @var null|string
     */
    protected $ruleNameBefore = null;

    /**
     * @var array
     */
    protected $codeBefore = [];

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon\Collection
     */
    protected $couponCollection;

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
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $couponCollection
     * @param \Bss\AdminActionLog\Model\Log $log
     * @param \Bss\AdminActionLog\Model\Config\Source\ActionInfo $actionInfo
     * @param \Magento\SalesRule\Model\Rule $rule
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\SalesRule\Model\ResourceModel\Coupon\Collection $couponCollection,
        \Bss\AdminActionLog\Model\Log $log,
        \Bss\AdminActionLog\Model\Config\Source\ActionInfo $actionInfo,
        \Magento\SalesRule\Model\Rule $rule
    ) {
        $this->request = $request;
        $this->couponCollection = $couponCollection;
        $this->log = $log;
        $this->actionInfo = $actionInfo;
        $this->rule = $rule;
    }

    /**
     * Get coupon code before delete
     *
     * @param mixed $subject
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute($subject)
    {
        $dataId = $this->request->getParam('ids') ?? $this->request->getParam('id') ;

        if (is_array($dataId)) { // Delete coupon code
            $couponsCollection = $this->couponCollection->addFieldToFilter(
                'coupon_id',
                ['in' => $dataId]
            );

            foreach ($couponsCollection as $coupon) {
                $this->codeBefore[$coupon->getId()] = $coupon->getCode();
            }
        } else if ($dataId) { // Delete cart rule
            $couponsCollection = $this->couponCollection->addFieldToFilter(
                'rule_id',
                $dataId
            );

            foreach ($couponsCollection as $coupon) {
                $this->codeBefore[$coupon->getId()] = $coupon->getCode();
            }
        }

        if (!empty($coupon)) {
            $this->ruleIdBefore = $coupon->getRuleId();

            $rule = $this->rule->load($this->ruleIdBefore);
            if ($rule && $rule->getUseAutoGeneration()) {
                $this->ruleNameBefore = $rule->getName();
            }
        }

        return null;
    }

    /**
     * Add log with coupon deleted.
     *
     * @param mixed $subject
     * @param mixed $result
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute($subject, $result)
    {
        if (!$this->ruleNameBefore || !$this->codeBefore) {
            return $result;
        }

        $couponsCollection = $this->couponCollection->addFieldToFilter(
            'rule_id',
            $this->ruleIdBefore
        );

        foreach ($couponsCollection as $coupon) {
            if (in_array($coupon->getId(), $this->codeBefore)) {
               unset($this->codeBefore[$coupon->getId()]); // Delete fail...
            }
        }

        $action = $this->actionInfo->toArray()['sales_rule_promo_quote_couponsMassDelete'];
        $log = [
            'group_action' => $action['label'],
            'group_name' => $action['group_name'],
            'info' => $this->ruleNameBefore,
            'action_type' => $action['action'],
            'action_name' => $action['controller_action'],
            'result' => '1', // empty error
            'store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID // default
        ];

        $index = 0;
        $logDetail[0]['source_data'] = $action['expected_models'];
        foreach ($this->codeBefore as $code) {
            ++$index;
            $logDetail[0]['old_value']['coupon_code_' . $index] = (string)$code;
            $logDetail[0]['new_value']['coupon_code_' . $index] = '';
        }

        $this->log->logCustomAction($log, $logDetail);

        return $result;
    }
}

<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Cron;

use Magenest\SagePay\Helper\Subscription;

class Daily
{
    protected $_profileFactory;

    private $sageLogger;

    /**
     * Daily constructor.
     * @param \Magenest\SagePay\Model\ResourceModel\Profile\CollectionFactory $profileFactory
     * @param \Magenest\SagePay\Helper\Logger $sageLogger
     */
    public function __construct(
        \Magenest\SagePay\Model\ResourceModel\Profile\CollectionFactory $profileFactory,
        \Magenest\SagePay\Helper\Logger                                 $sageLogger
    )
    {
        $this->_profileFactory = $profileFactory;
        $this->sageLogger = $sageLogger;
    }

    public function execute()
    {
        try {
            $allItems = $this->_profileFactory->create()
                ->addFieldToFilter('next_billing', date('Y-m-d'))
                ->addFieldToFilter('status', Subscription::SUBS_STAT_ACTIVE_CODE)
                ->getItems();
            $this->sageLogger->debug("sage cron begin");
            foreach ($allItems as $profile) {
                $profile->reOrder();
            }
        } catch (\Exception $exception) {
            $this->sageLogger->addError($exception->getMessage());
        }
        $this->sageLogger->debug("sage cron end");
    }
}

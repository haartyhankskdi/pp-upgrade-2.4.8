<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Helper;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\Helper\Context;

class Subscription extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SUBS_STAT_ACTIVE_CODE = 0;
    const SUBS_STAT_INACTIVE_CODE = 1;
    const SUBS_STAT_END_CODE = 2;
    const SUBS_STAT_CANCELLED_CODE = 3;
    const SUBS_STAT_ACTIVE_TEXT = "active";
    const SUBS_STAT_INACTIVE_TEXT = "inactive";
    const SUBS_STAT_END_TEXT = "end";
    const SUBS_STAT_CANCELLED_TEXT = "cancelled";

    protected $quoteFactory;
    protected $productFactory;
    protected $customerFactory;
    protected $customerRepository;

    public function __construct(
        Context $context,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        CustomerRepository $customerRepository
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->productFactory = $productFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item[] $items
     * @return bool
     */
    public function isSubscriptionItems($items)
    {
        if (!is_array($items)) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $buyRequest = $item->getBuyRequest();
            $additionalOptions = $buyRequest->getData('additional_options');
            if (is_array($additionalOptions)) {
                foreach ($additionalOptions as $key => $value) {
                    if ($key == "Billing Option") {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array
     */
    public function getSubscriptionData($item)
    {
        $return = [];
        $additionalOptions = $item->getBuyRequest()->getData('additional_options');
        foreach ($additionalOptions as $key => $value) {
            if ($key == "Billing Option") {
                // $value = "x cycles of y unit"
                $a = explode(" cycles of ", $value);
                $b = explode(" ", $a[1]);
                $return['total_cycles'] = empty($a[0]) ? "9999" : $a[0];
                $return['frequency'] = $b[0] . " " . $b[1];
                if ($b[0] > 1) {
                    $return['frequency'] .= "s";
                }
            }
        }

        return $return;
    }
}

<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Model;

use Magento\Framework\Model\AbstractModel;
use Magenest\SagePay\Helper\Subscription;

class Card extends AbstractModel
{
    protected $_eventPrefix = 'card_';

    /**
     * @param $customerId
     * @param $cardId
     * @param $last4
     * @throws \Exception
     */
    public function addCard($customerId, $cardId, $last4)
    {
        $data = [
            'customer_id' => $customerId,
            'card_id' => $cardId,
            'last_4' => $last4
        ];
        $this->setData($data)->save();
    }

    public function loadCards($customerId)
    {
        return $this->getCollection()->addFieldToFilter('customer_id', $customerId)->getData();
    }

    public function hasCard($customerId)
    {
        return $this->getCollection()->addFieldToFilter('customer_id', $customerId)->getSize() != 0;
    }

    public function isOwn($customerId)
    {
        return $this->getData('customer_id') == $customerId;
    }

    public function getAvailableStatuses()
    {
        return [
            Subscription::SUBS_STAT_ACTIVE_CODE => Subscription::SUBS_STAT_ACTIVE_TEXT,
            Subscription::SUBS_STAT_INACTIVE_CODE => Subscription::SUBS_STAT_INACTIVE_TEXT,
            Subscription::SUBS_STAT_END_CODE => Subscription::SUBS_STAT_END_TEXT,
            Subscription::SUBS_STAT_CANCELLED_CODE => Subscription::SUBS_STAT_CANCELLED_TEXT
        ];
    }
}

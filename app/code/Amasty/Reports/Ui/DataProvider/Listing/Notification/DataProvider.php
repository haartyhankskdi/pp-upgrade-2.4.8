<?php

declare(strict_types=1);

namespace Amasty\Reports\Ui\DataProvider\Listing\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        $intervalSource = $this->getConfigData()['intervalSource'];
        foreach ($data['items'] as &$item) {
            $item[NotificationInterface::STORE_IDS] = explode(',', $item[NotificationInterface::STORE_IDS]);
            $item[NotificationInterface::INTERVAL] = __(
                'Last %1 %2',
                $item[NotificationInterface::INTERVAL_QTY],
                $intervalSource->getLabelByValue((int)$item[NotificationInterface::INTERVAL])
            );
        }

        return $data;
    }
}

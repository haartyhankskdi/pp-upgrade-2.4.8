<?php

declare(strict_types=1);

namespace Amasty\Reports\Model;

use Amasty\Reports\Api\Data\NotificationInterface;

class Notification extends \Magento\Framework\Model\AbstractModel implements NotificationInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Reports\Model\ResourceModel\Notification::class);
    }

    public function getName(): string
    {
        return $this->_getData(NotificationInterface::NAME);
    }

    public function setName(string $name): NotificationInterface
    {
        $this->setData(NotificationInterface::NAME, $name);

        return $this;
    }

    public function getReports(): string
    {
        return $this->_getData(NotificationInterface::REPORTS);
    }

    public function setReports(string $reports): NotificationInterface
    {
        $this->setData(NotificationInterface::REPORTS, $reports);

        return $this;
    }

    public function getStoreIds(): string
    {
        return $this->_getData(NotificationInterface::STORE_IDS);
    }

    public function setStoreIds(string $storeIds): NotificationInterface
    {
        $this->setData(NotificationInterface::STORE_IDS, $storeIds);

        return $this;
    }

    public function getIntervalQty(): int
    {
        return (int)$this->_getData(NotificationInterface::INTERVAL_QTY);
    }

    public function setIntervalQty(int $qty): NotificationInterface
    {
        $this->setData(NotificationInterface::INTERVAL_QTY, $qty);

        return $this;
    }

    public function getInterval(): int
    {
        return (int)$this->_getData(NotificationInterface::INTERVAL);
    }

    public function setInterval(int $interval): NotificationInterface
    {
        $this->setData(NotificationInterface::INTERVAL, $interval);

        return $this;
    }

    public function getDisplayPeriod(): string
    {
        return $this->_getData(NotificationInterface::DISPLAY_PERIOD);
    }

    public function setDisplayPeriod(int $period): NotificationInterface
    {
        $this->setData(NotificationInterface::DISPLAY_PERIOD, $period);

        return $this;
    }

    public function getReceiver(): string
    {
        return $this->_getData(NotificationInterface::RECEIVER);
    }

    public function setReceiver(string $receiver): NotificationInterface
    {
        $this->setData(NotificationInterface::RECEIVER, $receiver);

        return $this;
    }

    public function getFrequency(): int
    {
        return $this->_getData(NotificationInterface::FREQUENCY);
    }

    public function setFrequency(int $frequency): NotificationInterface
    {
        $this->setData(NotificationInterface::FREQUENCY, $frequency);

        return $this;
    }

    public function getCronSchedule(): string
    {
        return $this->_getData(NotificationInterface::CRON_SCHEDULE);
    }

    public function setCronSchedule(string $schedule): NotificationInterface
    {
        $this->setData(NotificationInterface::CRON_SCHEDULE, $schedule);

        return $this;
    }
}

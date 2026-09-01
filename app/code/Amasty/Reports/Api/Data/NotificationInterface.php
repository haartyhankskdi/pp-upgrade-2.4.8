<?php

declare(strict_types=1);

namespace Amasty\Reports\Api\Data;

interface NotificationInterface
{
    public const TABLE_NAME = 'amasty_reports_notification';
    public const PERSIST_NAME = 'amasty_reports_notification';

    public const ENTITY_ID = 'entity_id';
    public const NAME = 'name';
    public const REPORTS = 'reports';
    public const STORE_IDS = 'store_ids';
    public const INTERVAL_QTY = 'interval_qty';
    public const INTERVAL = 'interval';
    public const DISPLAY_PERIOD = 'display_period';
    public const RECEIVER = 'receiver';
    public const FREQUENCY = 'frequency';
    public const CRON_SCHEDULE = 'cron_schedule';

    public function getName(): string;

    public function setName(string $name): NotificationInterface;

    public function getReports(): string;

    public function setReports(string $reports): NotificationInterface;

    public function getStoreIds(): string;

    public function setStoreIds(string $storeIds): NotificationInterface;

    public function getIntervalQty(): int;

    public function setIntervalQty(int $qty): NotificationInterface;

    public function getInterval(): int;

    public function setInterval(int $interval): NotificationInterface;

    public function getDisplayPeriod(): string;

    public function setDisplayPeriod(int $period): NotificationInterface;

    public function getReceiver(): string;

    public function setReceiver(string $receiver): NotificationInterface;

    public function getFrequency(): int;

    public function setFrequency(int $frequency): NotificationInterface;

    public function getCronSchedule(): string;

    public function setCronSchedule(string $schedule): NotificationInterface;
}

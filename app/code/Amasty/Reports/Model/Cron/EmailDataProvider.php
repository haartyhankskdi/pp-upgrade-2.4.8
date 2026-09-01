<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Cron;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Model\Source\Date\Interval;
use Amasty\Reports\Model\Source\Reports;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class EmailDataProvider
{
    /**
     * @var Interval
     */
    private $interval;

    /**
     * @var Reports
     */
    private $reports;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TimezoneInterface
     */
    private $date;

    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    public function __construct(
        Interval $interval,
        Reports $reports,
        StoreManagerInterface $storeManager,
        TimezoneInterface $date,
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->interval = $interval;
        $this->reports = $reports;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->notificationRepository = $notificationRepository;
    }

    public function getInterval(NotificationInterface $notification): string
    {
        return sprintf(
            '%s %s',
            $notification->getIntervalQty(),
            $this->interval->getLabelByValue($notification->getInterval())
        );
    }

    public function getReportLabels(string $reports): string
    {
        $reportLabels = [];
        foreach (explode(',', $reports) as $reportValue) {
            $reportLabels[] = $this->reports->getLabelByValue($reportValue);
        }

        return implode(',', $reportLabels);
    }

    public function getFileName(NotificationInterface $notification, string $reportValue, string $storeId): string
    {
        $fileName = sprintf(
            '%s_%s_%s_%s.csv',
            $this->reports->getLabelByValue($reportValue),
            $this->storeManager->getStore($storeId)->getName(),
            $this->date->date()->format('Y-m-d'),
            __('last %1', $this->getInterval($notification))
        );

        return $fileName;
    }

    public function getNotificationByJobCode(string $jobCode): ?NotificationInterface
    {
        $jobCodeParts = explode('_', $jobCode);
        if (count($jobCodeParts) != 4) {
            return null;
        }

        $notifId = (int)$jobCodeParts[3];
        try {
            $notification = $this->notificationRepository->getById($notifId);
        } catch (NoSuchEntityException $e) {
            $notification = null;
        }

        return $notification;
    }
}

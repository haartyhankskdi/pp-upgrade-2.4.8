<?php

declare(strict_types=1);

namespace Amasty\Reports\Plugin\Cron\Model;

use Amasty\Reports\Api\NotificationRepositoryInterface;

class ConfigPlugin
{
    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    public function __construct(
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->notificationRepository = $notificationRepository;
    }

    public function afterGetJobs(\Magento\Cron\Model\Config $subject, array $jobs): array
    {
        $currentNotif = $jobs['default']['amasty_reports_notification'] ?? false;
        if (!$currentNotif) {
            return $jobs;
        }

        foreach ($this->notificationRepository->getAll() as $notification) {
            $currentNotif['schedule'] = $notification->getCronSchedule();
            $jobs['default']['amasty_reports_notification_' . $notification->getEntityId()] = $currentNotif;
        }

        unset($jobs['default']['amasty_reports_notification']);

        return $jobs;
    }
}

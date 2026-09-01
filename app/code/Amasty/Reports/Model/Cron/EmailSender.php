<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Cron;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Model\ConfigProvider;
use Amasty\Reports\Model\Email\ReportContent;
use Magento\Cms\Ui\Component\Listing\Column\Cms\Options;
use Magento\Cron\Model\Schedule;
use Amasty\Reports\Model\Email\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var EmailDataProvider
     */
    private $dataProvider;

    /**
     * @var ReportContent
     */
    private $reportContent;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        ConfigProvider $configProvider,
        EmailDataProvider $dataProvider,
        ReportContent $reportContent,
        StoreManagerInterface $storeManager
    ) {
        $this->configProvider = $configProvider;
        $this->transportBuilder = $transportBuilder;
        $this->dataProvider = $dataProvider;
        $this->reportContent = $reportContent;
        $this->storeManager = $storeManager;
    }

    public function execute(Schedule $schedule): bool
    {
        $notification = $this->dataProvider->getNotificationByJobCode($schedule->getJobCode());
        if ($notification) {
            $this->sendEmail($notification);
        }

        return true;
    }

    private function sendEmail(NotificationInterface $notification)
    {
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $this->configProvider->getNotificationTemplate()
        )->setTemplateOptions(
            [
                'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => 0
            ]
        )->setTemplateVars(
            [
                'notification_name' => $notification->getName(),
                'time_interval' => $this->dataProvider->getInterval($notification),
                'reports' => $this->dataProvider->getReportLabels($notification->getReports())
            ]
        )->setFromByScope(
            $this->configProvider->getNotificationSender()
        )->addTo(
            explode(',', $notification->getReceiver())
        );

        $this->addAttachment($transport, $notification);

        $transport->getTransport()->sendMessage();
    }

    private function addAttachment(TransportBuilder $transport, NotificationInterface $notification): void
    {
        foreach (explode(',', $notification->getReports()) as $reportValue) {
            foreach ($this->getStoreIds($notification) as $storeId) {
                $transport->addAttachment(
                    $this->reportContent->getContent($notification, $reportValue, $storeId),
                    $this->dataProvider->getFileName($notification, $reportValue, $storeId)
                );
            }
        }
    }

    private function getStoreIds(NotificationInterface $notification): array
    {
        $storeIds = explode(',', $notification->getStoreIds());
        if (in_array(Options::ALL_STORE_VIEWS, $storeIds)) {
            $storeIds = [];
            foreach ($this->storeManager->getStores() as $store) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }
}

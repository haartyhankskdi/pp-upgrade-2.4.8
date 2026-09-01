<?php

declare(strict_types=1);

namespace Amasty\Reports\Ui\DataProvider\Form\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Model\Registry;
use Amasty\Reports\Model\ResourceModel\Notification\CollectionFactory;
use Amasty\Reports\Model\Notification;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    public const COUNT_CRON_VALUES = 5;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        NotificationRepositoryInterface $notificationRepository,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $result = parent::getData();

        /** @var Notification $current */
        $current = $this->registry->registry(NotificationInterface::PERSIST_NAME);
        if ($current && $current->getEntityId()) {
            $data = $current->getData();
            $result[$current->getEntityId()] = $this->prepareData($data);
        } else {
            $data = $this->dataPersistor->get(NotificationInterface::PERSIST_NAME);
            if (!empty($data)) {
                $notification = $this->notificationRepository->getNewNotification();
                $notification->setData($data);
                $result[$notification->getId()] = $notification->getData();
                $this->dataPersistor->clear(NotificationInterface::PERSIST_NAME);
            }
        }

        return $result;
    }

    private function prepareData(array $data): array
    {
        if (isset($data[NotificationInterface::CRON_SCHEDULE])) {
            $dates = explode(' ', $data[NotificationInterface::CRON_SCHEDULE]);
            if (count($dates) == self::COUNT_CRON_VALUES) {
                $data['minutes'] = $dates[0];
                $data['hours'] = $dates[1];
                $data['days'] = $dates[2];
                $data['months'] = $dates[3];
                $data['days_of_week'] = $dates[4];
            }
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Controller\Adminhtml\Notification;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Save extends Notification
{
    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        NotificationRepositoryInterface $notificationRepository,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->notificationRepository = $notificationRepository;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
    }

    public function execute()
    {
        $isError = false;
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $data = $this->getRequest()->getPostValue();
        $notifId = (int)$this->getRequest()->getParam(NotificationInterface::ENTITY_ID);
        if ($data) {
            $model = $this->getNotification($notifId, $data);
            try {
                $data = $this->prepareData($data);
                $model->addData($data);
                $model = $this->notificationRepository->save($model);

                $this->messageManager->addSuccessMessage(__('The Notification was successfully saved.'));
                $this->dataPersistor->clear(NotificationInterface::PERSIST_NAME);

                if ($this->getRequest()->getParam('back')) {
                    $resultRedirect->setPath('amasty_reports/*/edit', ['id' => $model->getEntityId()]);
                }
            } catch (LocalizedException $e) {
                $isError = true;
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $isError = true;
                $this->messageManager->addErrorMessage(
                    __('Something went wrong while saving the rule data. Please review the error log.')
                );
                $this->logger->critical($e);
            }

            if ($isError) {
                $this->setRedirect($resultRedirect, $notifId, $data);
            }
        }

        return $resultRedirect;
    }

    private function setRedirect(Redirect $resultRedirect, int $notifId, array $data)
    {
        $this->dataPersistor->set(NotificationInterface::PERSIST_NAME, $data);
        if (empty($notifId)) {
            $resultRedirect->setPath('amasty_reports/*/newAction');
        } else {
            $resultRedirect->setPath('amasty_reports/*/edit', ['id' => $notifId]);
        }
    }

    private function getNotification(int $notifId, array &$data): NotificationInterface
    {
        try {
            /** @var \Amasty\Reports\Model\Notification $model */
            if ($notifId) {
                $model = $this->notificationRepository->getById($notifId);
            } else {
                $model = $this->notificationRepository->getNewNotification();
                unset($data[NotificationInterface::ENTITY_ID]);
            }
        } catch (NoSuchEntityException $e) {
            $model = $this->notificationRepository->getNewNotification();
        }

        return $model;
    }

    private function prepareData(array $data): array
    {
        $data[NotificationInterface::CRON_SCHEDULE] = sprintf(
            '%s %s %s %s %s',
            $data['minutes'],
            $data['hours'],
            $data['days'],
            $data['months'],
            $data['days_of_week']
        );
        $data[NotificationInterface::STORE_IDS] = implode(',', $data[NotificationInterface::STORE_IDS]);
        $data[NotificationInterface::REPORTS] = implode(',', $data[NotificationInterface::REPORTS]);

        return $data;
    }
}

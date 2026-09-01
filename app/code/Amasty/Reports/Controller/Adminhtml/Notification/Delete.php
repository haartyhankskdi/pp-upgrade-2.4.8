<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Controller\Adminhtml\Notification;
use Magento\Backend\App\Action;
use Psr\Log\LoggerInterface;

class Delete extends Notification
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    public function __construct(
        Action\Context $context,
        NotificationRepositoryInterface $notificationRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('amasty_reports/*');
        $notificationId = (int)$this->getRequest()->getParam('id');
        if ($notificationId) {
            try {
                $this->notificationRepository->deleteById($notificationId);
                $this->messageManager->addSuccessMessage(__('The Notification has been deleted.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete item right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
                $resultRedirect->setPath('amasty_reports/*/edit', ['id' => $notificationId]);
            }
        }

        return $resultRedirect;
    }
}

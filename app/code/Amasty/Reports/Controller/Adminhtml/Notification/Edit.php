<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Amasty\Reports\Model\Registry;
use Magento\Backend\App\Action;

class Edit extends \Amasty\Reports\Controller\Adminhtml\Notification
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    public function __construct(
        NotificationRepositoryInterface $notificationRepository,
        DataPersistorInterface $dataPersistor,
        Registry $registry,
        Action\Context $context
    ) {
        parent::__construct($context);
        $this->notificationRepository = $notificationRepository;
        $this->dataPersistor = $dataPersistor;
        $this->registry = $registry;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $notifId = (int)$this->getRequest()->getParam('id');
        if ($notifId) {
            try {
                $model = $this->notificationRepository->getById($notifId);
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This Notification no longer exists.'));
                // phpcs:ignore Magento2.Legacy.ObsoleteResponse.RedirectResponseMethodFound
                $this->_redirect('*/*/index');

                return;
            }
        } else {
            $model = $this->notificationRepository->getNewNotification();
        }

        $data = $this->dataPersistor->get(NotificationInterface::PERSIST_NAME);
        if (!empty($data) && !$model->getEntityId()) {
            $model->addData($data);
        }

        $this->registry->register(NotificationInterface::PERSIST_NAME, $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $breadcrumb = $model->getEntityId() ? __('Edit Notification') : __('New Notification');
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb);
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getEntityId() ? __('Edit Notification') : __('New Notification')
        );

        return $resultPage;
    }
}

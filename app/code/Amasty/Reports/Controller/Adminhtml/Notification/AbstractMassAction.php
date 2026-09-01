<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;
use Amasty\Reports\Controller\Adminhtml\Notification;
use Amasty\Reports\Api\NotificationRepositoryInterface;
use Amasty\Reports\Model\ResourceModel\Notification\Collection as NotificationCollection;
use Amasty\Reports\Model\ResourceModel\Notification\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

abstract class AbstractMassAction extends Notification
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var NotificationRepositoryInterface
     */
    protected $repository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        LoggerInterface $logger,
        NotificationRepositoryInterface $repository,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->collectionFactory = $collectionFactory;
    }

    abstract protected function itemAction(NotificationInterface $notification);

    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();
        /** @var NotificationCollection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $collectionSize = $collection->getSize();
        if ($collectionSize) {
            try {
                /** @var NotificationInterface $model */
                foreach ($collection->getItems() as $model) {
                    $this->itemAction($model);
                }

                $this->messageManager->addSuccessMessage($this->getSuccessMessage($collectionSize));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($this->getErrorMessage());
                $this->logger->critical($e);
            }
        }

        // phpcs:ignore Magento2.Legacy.ObsoleteResponse.RedirectResponseMethodFound
        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    protected function getErrorMessage(): Phrase
    {
        return __('We can\'t change item right now. Please review the log and try again.');
    }

    protected function getSuccessMessage(int $collectionSize = 0): Phrase
    {
        if ($collectionSize) {
            return __('A total of %1 record(s) have been changed.', $collectionSize);
        }

        return __('No records have been changed.');
    }
}

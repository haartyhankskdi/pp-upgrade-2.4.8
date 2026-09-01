<?php

declare(strict_types=1);

namespace Amasty\Reports\Controller\Adminhtml\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;
use Magento\Framework\Phrase;

class MassDelete extends AbstractMassAction
{
    protected function itemAction(NotificationInterface $notification)
    {
        $this->repository->deleteById((int)$notification->getEntityId());
    }

    /**
     * @return Phrase
     */
    protected function getErrorMessage(): Phrase
    {
        return __('We can\'t delete item right now. Please review the log and try again.');
    }

    protected function getSuccessMessage(int $collectionSize = 0): Phrase
    {
        if ($collectionSize) {
            return __('A total of %1 record(s) have been deleted.', $collectionSize);
        }

        return __('No records have been deleted.');
    }
}

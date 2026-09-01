<?php

declare(strict_types=1);

namespace Amasty\Reports\Block\Adminhtml\Notification;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $data = [];
        $notifId = $this->getNotificationId();
        if ($notifId) {
            $message = __('Are you sure you want to delete this notification?');
            $url = $this->getUrl('*/*/delete', ['id' => $notifId]);
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => sprintf('deleteConfirm("%s", "%s")', $message, $url),
                'sort_order' => 20,
            ];
        }

        return $data;
    }
}

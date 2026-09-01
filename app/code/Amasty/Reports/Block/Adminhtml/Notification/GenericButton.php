<?php

declare(strict_types=1);

namespace Amasty\Reports\Block\Adminhtml\Notification;

use Amasty\Reports\Api\Data\NotificationInterface;

class GenericButton
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Amasty\Reports\Model\Registry
     */
    private $registry;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Amasty\Reports\Model\Registry $registry
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
    }

    public function getNotificationId(): ?int
    {
        $notification = $this->registry->registry(NotificationInterface::PERSIST_NAME);

        return $notification ? (int)$notification->getEntityId() : null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}

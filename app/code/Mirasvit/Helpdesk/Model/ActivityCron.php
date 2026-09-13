<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model;

use Magento\Backend\Model\Url;
use Mirasvit\Helpdesk\Model\ResourceModel\Message\Collection as MessageCollection;
use Mirasvit\Helpdesk\Api\Data\ActivityInterface;
use Mirasvit\Helpdesk\Repository\ActivityRepository;
use Magento\Framework\App\ResourceConnection;

class ActivityCron
{
    private $activityRepository;

    private $messageCollection;

    private $urlBuilder;

    private $resource;

    public function __construct(
        ActivityRepository $activityRepository,
        MessageCollection $messageCollection,
        Url $urlBuilder,
        ResourceConnection $resource
    ) {
        $this->activityRepository = $activityRepository;
        $this->messageCollection  = $messageCollection;
        $this->urlBuilder         = $urlBuilder;
        $this->resource           = $resource;
    }

    public function execute(): void
    {
        $this->collectHelpdesk(3);
    }

    private function collectHelpdesk($depth = 1)
    {
        $ticketTable = $this->resource->getTableName('mst_helpdesk_ticket');
        $messageCollection = $this->messageCollection;
        $messageCollection->addFieldToFilter('main_table.user_id', ['gt' => 0]);
        $from = date('Y-m-d H:i:s', (int) strtotime("-$depth hours", time()));

        $messageCollection->addFieldToFilter('main_table.created_at', ['gteq' => $from]);
        $messageCollection->getSelect()
            ->joinLeft(['t' => $ticketTable], 't.ticket_id=main_table.ticket_id', ['code', 'subject']);

        /** @var \Mirasvit\Helpdesk\Model\Message $message */
        foreach ($messageCollection as $message) {
            $externalId  = 'message' . $message->getId();
            $kind        = 'HelpdeskMessage';
            $timestamp   = $message->getCreatedAt();
            $userId      = $message->getUserId();
            $title       = __('[%1] %2', $message->getData('code'), $message->getData('subject'));
            $description = $message->getUnsafeBodyHtml();
            $url         = $this->urlBuilder->getUrl('helpdesk/ticket/edit', ['id' => $message->getTicketId()]);
            $payload     = $message->getId();

            $this->addActivity(
                $externalId,
                $kind,
                $timestamp,
                $userId,
                $title,
                $description,
                $url,
                $payload
            );
        }
    }

    private function addActivity($externalId, $kind, $timestamp, $userId, $title, $description, $url, $payload): void
    {
        if (is_numeric($timestamp)) {
            $timestamp = date('Y-m-d H:i:s', (int) $timestamp);
        }

        /** @var ActivityInterface $activity */
        $activity = $this->activityRepository->getCollection()
            ->addFieldToFilter(ActivityInterface::EXTERNAL_ID, $externalId)
            ->getFirstItem();

        $activity->setExternalId($externalId)
            ->setKind($kind)
            ->setTimestamp($timestamp)
            ->setUserId($userId)
            ->setTitle($title)
            ->setDescription($description)
            ->setUrl($url)
            ->setPayload($payload);

        $this->activityRepository->save($activity);
    }
}

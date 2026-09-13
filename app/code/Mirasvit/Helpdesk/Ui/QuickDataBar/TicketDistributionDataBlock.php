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



namespace Mirasvit\Helpdesk\Ui\QuickDataBar;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Core\Ui\QuickDataBar\MultiRowDataBlock;
use Mirasvit\Helpdesk\Api\Data\StatusInterface;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config;

class TicketDistributionDataBlock extends MultiRowDataBlock
{
    private $resource;

    public function __construct(
        ResourceConnection $resource,
        Template\Context $context
    ) {
        $this->resource = $resource;

        parent::__construct($context);
    }

    public function getCode(): string
    {
        return 'helpdesk_ticket_distribution';
    }

    public function getProviderName(): string
    {
        return 'helpdesk_ticket_listing.helpdesk_ticket_listing.listing_top.listing_filters';
    }

    public function getLabel(): string
    {
        return '';
    }

    public function getRows(): array
    {
        return [
            [
                'data'       => $this->getUserTickets(),
                'provider'   => $this->getProviderName(),
                'filter'     => TicketInterface::KEY_USER_ID,
                'conditions' => [
                    'helpdesk_ticket_listing.helpdesk_ticket_listing.listing_top.listing_filters.folder' => Config::FOLDER_INBOX,
                ],
            ],
            [
                'data'     => $this->getStatusTickets(),
                'provider' => $this->getProviderName(),
                'filter'   => TicketInterface::KEY_STATUS_ID,
                'conditions' => [
                    'helpdesk_ticket_listing.helpdesk_ticket_listing.listing_top.listing_filters.folder' => Config::FOLDER_INBOX,
                ],
            ],
        ];
    }

    public function getUserTickets(): array
    {
        $select = $this->getUserSelect();

        $rows = $this->resource->getConnection()
            ->fetchAll($select);
        $data = [];

        foreach ($rows as $row) {
            $index = $row['value'] > 100 ? 10 : (round((int)$row['value'] / 10, 0, PHP_ROUND_HALF_DOWN));

            $color    = 'color' . $index;
            $progress = 'progress' . $index;

            $data[] = [
                'label'     => $row['adminname'],
                'value'     => $row['value'],
                'filter'    => $row['user_id'],
                'isLink'    => true,
                'cellClass' => 'progress-bar ' . $progress . ' cell-' . $color,
            ];
        }

        return $data;
    }

    public function getUserSelect(array $columns = []): Select
    {
        $columns = array_merge($columns, [
            'value'     => new \Zend_Db_Expr('COUNT(ticket_id)'),
            'ticket.user_id',
            'adminname' => new \Zend_Db_Expr('CONCAT(au.firstname, " ", au.lastname)'),
        ]);

        return $this->resource->getConnection()
            ->select()
            ->from(['ticket' => $this->resource->getTableName(TicketInterface::TABLE_NAME)], $columns)
            ->joinLeft([
                'au' => $this->resource->getTableName('admin_user'),
            ], 'ticket.user_id = au.user_id', [
                'au.firstname',
                'au.lastname',
            ])
            ->where('ticket.' . TicketInterface::KEY_USER_ID . ' > 0 AND ' .
                TicketInterface::KEY_FOLDER . ' = ' . Config::FOLDER_INBOX)
            ->group('ticket.' . TicketInterface::KEY_USER_ID)
            ->order('value DESC');
    }

    public function getStatusTickets(): array
    {
        $data = [];

        $select = $this->getStatusSelect();

        $rows = $this->resource->getConnection()
            ->fetchAll($select);

        foreach ($rows as $row) {
            $name  = SerializeService::decode($row['name']);
            if ($name && is_array($name)){
                $name = array_shift($name);
            }

            $index = $row['value'] > 100 ? 10 : (round((int)$row['value'] / 10, 0, PHP_ROUND_HALF_DOWN));

            $progress = 'progress' . $index;

            $data[] = [
                'label'     => (string)$name ? $name . ':' : $row['name'],
                'value'     => $row['value'],
                'filter'    => $row['status_id'],
                'isLink'    => true,
                'cellClass' => 'progress-bar ' . $progress . ' cell-' . $row['color'],
            ];
        }

        return $data;
    }

    public function getStatusSelect(array $columns = []): Select
    {
        $columns = array_merge($columns, [
            'value'     => new \Zend_Db_Expr('COUNT(*)'),
            'status_id' => 'ticket.status_id',
        ]);

        return $this->resource->getConnection()
            ->select()
            ->from(['ticket' => $this->resource->getTableName(TicketInterface::TABLE_NAME)], $columns)
            ->joinLeft([
                'ts' => $this->resource->getTableName(StatusInterface::TABLE_NAME),
            ], 'ticket.status_id = ts.status_id', [
                'name'  => new \Zend_Db_Expr('ts.name'),
                'color' => 'ts.color',
            ])
            ->where(TicketInterface::KEY_FOLDER . ' = ' . Config::FOLDER_INBOX)
            ->group('ticket.' . TicketInterface::KEY_STATUS_ID)
            ->order('value DESC');
    }
}

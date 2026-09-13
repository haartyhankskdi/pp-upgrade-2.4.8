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
use Magento\Backend\Model\Auth;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Mirasvit\Core\Ui\QuickDataBar\MultiRowDataBlock;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config;

class MyTicketDataBlock extends MultiRowDataBlock
{
    private $auth;

    private $resource;

    private $ticketDistributionDataBlock;

    public function __construct(
        TicketDistributionDataBlock $ticketDistributionDataBlock,
        Auth                        $auth,
        ResourceConnection          $resource,
        Template\Context            $context
    ) {
        $this->ticketDistributionDataBlock = $ticketDistributionDataBlock;
        $this->auth                        = $auth;
        $this->resource                    = $resource;

        parent::__construct($context);
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
                'provider'   => $this->ticketDistributionDataBlock->getProviderName(),
                'filter'     => TicketInterface::KEY_USER_ID,
                'conditions' => [
                    'helpdesk_ticket_listing.helpdesk_ticket_listing.listing_top.listing_filters.folder' => Config::FOLDER_INBOX,
                ],
            ],
        ];
    }

    public function getUserTickets(): array
    {
        $select = $this->getUserSelect($this->auth->getUser()->getId());

        $rows = $this->resource->getConnection()
            ->fetchAll($select);
        $data = [];

        foreach ($rows as $row) {
            $index = $row['value'] > 100 ? 10 : (round((int)$row['value'] / 10, 0, PHP_ROUND_HALF_DOWN));

            $color    = 'color' . $index;
            $progress = 'progress' . $index;

            $data[] = [
                'label'     => __('My Tickets'),
                'value'     => $row['value'],
                'filter'    => $row['user_id'],
                'isLink'    => true,
                'cellClass' => 'progress-bar ' . $progress . ' cell-' . $color,
            ];
        }

        return $data;
    }

    public function getUserSelect(int $currentUserId, array $columns = []): Select
    {
        $columns = array_merge($columns, [
            'value'     => new \Zend_Db_Expr('COUNT(ticket_id)'),
            'ticket.user_id',
            'adminname' => new \Zend_Db_Expr('CONCAT(au.firstname, " ", au.lastname)'),
        ]);

        return $this->resource->getConnection()
            ->select()
            ->from(
                ['ticket' => $this->resource->getTableName(TicketInterface::TABLE_NAME)],
                $columns
            )
            ->joinLeft(
                ['au' => $this->resource->getTableName('admin_user')],
                'ticket.user_id = au.user_id',
                ['au.firstname', 'au.lastname']
            )
            ->where('ticket.' . TicketInterface::KEY_USER_ID . ' = ?', $currentUserId)
            ->where('ticket.' . TicketInterface::KEY_FOLDER . ' = ?', Config::FOLDER_INBOX)
            ->order('value DESC');
    }
}

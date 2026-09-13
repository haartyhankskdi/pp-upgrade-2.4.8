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
use Mirasvit\Core\Ui\QuickDataBar\MultiRowDataBlock;
use Mirasvit\Helpdesk\Api\Data\TicketInterface;
use Mirasvit\Helpdesk\Model\Config;

class AllTicketDataBlock extends MultiRowDataBlock
{
    private $resource;

    private $ticketDistributionDataBlock;

    public function __construct(
        TicketDistributionDataBlock $ticketDistributionDataBlock,
        ResourceConnection          $resource,
        Template\Context            $context
    ) {
        $this->ticketDistributionDataBlock = $ticketDistributionDataBlock;
        $this->resource                    = $resource;

        parent::__construct($context);
    }

    public function getCode(): string
    {
        return 'helpdesk_all_tickets';
    }

    public function getLabel(): string
    {
        return '';
    }

    public function getRows(): array
    {
        return [
            [
                'data'       => $this->getSelectTickets(),
                'provider'   => $this->ticketDistributionDataBlock->getProviderName(),
                'filter'     => TicketInterface::KEY_USER_ID,
                'conditions' => [
                    'helpdesk_ticket_listing.helpdesk_ticket_listing.listing_top.listing_filters.folder' => Config::FOLDER_INBOX,
                ],
            ],
        ];
    }

    public function getSelectTickets(): array
    {
        $select = $this->getSelect();

        $value = (int)$this->resource->getConnection()
            ->fetchOne($select);
        $index = $value > 100 ? 10 : (round((int)$value / 10, 0, PHP_ROUND_HALF_DOWN));

        $color    = 'color' . $index;

        $data[] = [
            'label'     => __('All Tickets'),
            'value'     => $value,
            'isLink'    => true,
            'cellClass' => 'progress-bar ' . ' cell-' . $color,
        ];

        return $data;
    }

    public function getSelect(array $columns = []): Select
    {
        $columns = array_merge($columns, [
            'value' => new \Zend_Db_Expr('COUNT(' . TicketInterface::KEY_ID . ')'),
        ]);

        return $this->resource->getConnection()
            ->select()
            ->from($this->resource->getTableName(TicketInterface::TABLE_NAME), $columns)
            ->where(TicketInterface::KEY_FOLDER . ' = ' . Config::FOLDER_INBOX);
    }
}

<?php

namespace Kdi\Popup\Controller\Adminhtml\Email;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Ui\Component\MassAction\Filter;
use Kdi\Popup\Model\ResourceModel\Email\CollectionFactory;

/**
 * Exports all (or filtered) popup email subscribers to a CSV file.
 */
class ExportCsv extends Action
{
    const ADMIN_RESOURCE = 'Kdi_Popup::email';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private FileFactory $fileFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $collection = $this->collectionFactory->create();

        $rows = [['ID', 'Email', 'Product ID', 'IP Address', 'Created At']];
        foreach ($collection as $item) {
            $rows[] = [
                $item->getEntityId(),
                $item->getEmail(),
                $item->getProductId(),
                $item->getCustomerIp(),
                $item->getCreatedAt(),
            ];
        }

        $content = '';
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) {
                return '"' . str_replace('"', '""', (string)$value) . '"';
            }, $row);
            $content .= implode(',', $escaped) . "\n";
        }

        $fileName = 'popup_email_subscribers_' . date('Y-m-d_H-i-s') . '.csv';

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}

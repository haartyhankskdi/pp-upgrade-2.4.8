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
 * @package   mirasvit/module-report
 * @version   1.4.74
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Report\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Mirasvit\ReportApi\Api\Config\TypeInterface;
use Mirasvit\ReportApi\Api\Processor\ResponseColumnInterface;
use Mirasvit\ReportApi\Api\Processor\ResponseItemInterface;
use Mirasvit\ReportApi\Api\ResponseInterface;
use Mirasvit\ReportApi\Api\RequestInterface;

class ConvertToCsv
{
    const EXPORT_DIR = 'export';

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * ConvertToCsv constructor.
     * @param Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getCsvFile(RequestInterface $request)
    {
        /** @var \Mirasvit\Report\Model\Config $config */
        $config = ObjectManager::getInstance()->get('\Mirasvit\Report\Model\Config');
        $name = hash('sha256', microtime());
        $file = self::EXPORT_DIR . '/' . $name . '.csv';

        $this->directory->create(self::EXPORT_DIR);
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $header = [];
        $totals = null;

        $request->setPageSize(1000);

        for ($i = 1; true; $i++) {
            $r = clone $request;
            $r->setCurrentPage($i);
            // Totals are the same for every page, so compute them once on the first page and
            // disable them afterwards — otherwise every page re-runs the full grouped totals
            // query, exhausting memory on large datasets (mirasvit/pm#562).
            $r->setCalculateTotals($i === 1);
            $resp = $r->process();

            if($i === 1) {
                foreach ($resp->getColumns() as $column) {
                    $header[] = $column->getLabel();
                }
                $stream->writeCsv($header);

                $totals = $resp->getTotals();
            }

            if(!count($resp->getItems())) {
                $stream->writeCsv(
                    $config->formatCsvData()
                        ? $totals->getFormattedData()
                        : $totals->getData()
                );
                break;
            }

            foreach ($resp->getItems() as $item) {
                $this->writeItem($stream, $item, $resp->getColumns());
            }

        }

        $stream->unlock();
        $stream->close();

        return [
            'type'  => 'filename',
            'value' => $file,
            'rm'    => true // can delete file after use
        ];
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function writeItem(
        Filesystem\File\WriteInterface $stream,
        ResponseItemInterface $item,
        array $columns
    ) {
        $formattedData = $item->getFormattedData();
        $rawData       = $item->getData();
        /** @var \Mirasvit\Report\Model\Config $config */
        $config = ObjectManager::getInstance()->get('\Mirasvit\Report\Model\Config');

        $data = [];
        /** @var ResponseColumnInterface $column */
        foreach ($columns as $column) {
            $name = $column->getName();
            $type = $column->getType();

            if (
                !$config->formatCsvData()
                && in_array($type, [TypeInterface::TYPE_MONEY, TypeInterface::TYPE_PERCENT, TypeInterface::TYPE_DATE])
            ) {
                $outputData = $rawData;
            } else {
                $outputData = $formattedData;
            }

            if (isset($outputData[$name])) {
                $data[] = $outputData[$name];
            } else {
                $data[] = '';
            }
        }

        $stream->writeCsv($data);

        foreach ($item->getItems() as $subItem) {
            $this->writeItem($stream, $subItem, $columns);
        }
    }
}

<?php

// phpcs:ignoreFile
namespace Amasty\Reports\Model\Grid\Export\Sales\Overview;

use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToXml as OriginalConvertToXml;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;

class ConvertToXml extends OriginalConvertToXml
{
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        MetadataProvider $metadataProvider,
        ExcelFactory $excelFactory,
        SearchResultIteratorFactory $iteratorFactory
    ) {
        parent::__construct($filesystem, $filter, $metadataProvider, $excelFactory, $iteratorFactory);
    }
}

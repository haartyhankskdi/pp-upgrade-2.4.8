<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Email;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\MassAction\Filter;

class CsvGenerator
{
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var array
     */
    private $metaDataProviders;

    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        array $metaDataProviders = []
    ) {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->filter = $filter;
        $this->metaDataProviders = $metaDataProviders;
    }

    public function getCsvContent(string $report): string
    {
        $file = $this->createCsvFile($report);

        $content = $this->directory->readFile($file);
        $this->directory->delete($file);

        return $content;
    }

    private function createCsvFile(string $report): string
    {
        $file = sprintf('export/%s.csv', $report);
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $this->addFieldsToFile($this->prepareComponent(), $stream, $report);

        $stream->unlock();
        $stream->close();

        return $file;
    }

    private function prepareComponent(): UiComponentInterface
    {
        $component = $this->filter->getComponent();
        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();

        return $component;
    }

    private function addFieldsToFile(UiComponentInterface $component, WriteInterface $stream, string $report)
    {
        $metaDataProvider = $this->metaDataProviders[$report] ?? $this->metaDataProviders['default'];
        $fields = $metaDataProvider->getFields($component);
        $options = $metaDataProvider->getOptions();
        $dataProvider = $component->getContext()->getDataProvider();
        $items = $dataProvider->getSearchResult()->getItems();

        $stream->writeCsv($metaDataProvider->getHeaders($component));
        foreach ($items as $item) {
            $metaDataProvider->convertDate($item, $component->getName());
            $stream->writeCsv($metaDataProvider->getRowData($item, $fields, $options));
        }
    }
}

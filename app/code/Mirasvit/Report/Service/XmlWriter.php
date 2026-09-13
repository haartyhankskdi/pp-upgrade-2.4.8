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




namespace Mirasvit\Report\Service;


use Magento\Framework\Convert\Excel;
use Magento\Framework\Filesystem\File\WriteInterface;
use Mirasvit\ReportApi\Api\Processor\ResponseItemInterface;

class XmlWriter extends Excel
{
    /**
     * @param WriteInterface $stream
     * @param string         $sheetName
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write(WriteInterface $stream, $sheetName = '')
    {
        $stream->write($this->_getXmlHeader($sheetName));

        foreach (($this->_iterator ?? []) as $dataRow) {
            $this->writeRecursive($stream, $dataRow);
        }
        $stream->write($this->_getXmlFooter());
    }

    /**
     * {@inheritdoc}
     * @param mixed $row
     */
    protected function _getXmlRow($row, $useCallback)
    {
        if ($useCallback && is_callable($this->_rowCallback)) {
            $row = call_user_func($this->_rowCallback, $row);
        }

        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return parent::_getXmlRow($row, false);
    }

    /**
     * @param WriteInterface        $stream
     * @param ResponseItemInterface $dataRow
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function writeRecursive(WriteInterface $stream, $dataRow)
    {
        $orData = $dataRow;
        $stream->write($this->_getXmlRow($dataRow, true));

        foreach ($orData->getItems() as $subRow) {
            $this->writeRecursive($stream, $subRow);
        }
    }
}
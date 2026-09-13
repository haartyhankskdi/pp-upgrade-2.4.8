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
 * @package   mirasvit/module-report-api
 * @version   1.0.95
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\ReportApi\Api\Processor;

interface ResponseItemInterface
{
    /**
     * @return string[]
     */
    public function getData(): array;

    /**
     * @return string[]
     */
    public function getFormattedData(): array;

    /**
     * @return \Mirasvit\ReportApi\Api\Processor\ResponseItemInterface[]
     */
    public function getItems();

    /**
     * @param string $key
     * @return string|null
     */
    public function getDataByKey(string $key): ?string;

    /**
     * @param string $key
     * @return string|null
     */
    public function getFormattedDataByKey(string $key): ?string;
}

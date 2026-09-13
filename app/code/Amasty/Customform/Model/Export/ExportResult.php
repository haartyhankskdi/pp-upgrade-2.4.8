<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Export;

class ExportResult implements ExportResultInterface
{
    /**
     * @var string
     */
    private $rawResult;

    /**
     * @var string
     */
    private $resultName;

    public function __construct(
        string $rawResult = '',
        string $name = ''
    ) {
        $this->resultName = $name;
        $this->rawResult = $rawResult;
    }

    public function getRaw(): string
    {
        return $this->rawResult;
    }

    public function getName(): string
    {
        return $this->resultName;
    }
}

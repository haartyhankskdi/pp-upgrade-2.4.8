<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Answser\Email;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class FieldsDataProvider implements ArgumentInterface
{
    private $fieldsData = [];

    /**
     * @return DataObject[]
     */
    public function getFieldsData(): array
    {
        return $this->fieldsData;
    }

    /**
     * @param DataObject[] $fieldsData
     */
    public function setFieldsData(array $fieldsData): void
    {
        $this->fieldsData = $fieldsData;
    }
}

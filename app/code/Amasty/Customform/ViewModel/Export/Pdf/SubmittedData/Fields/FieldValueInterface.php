<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields;

use Magento\Framework\View\Element\Block\ArgumentInterface;

interface FieldValueInterface extends ArgumentInterface
{
    /**
     * @return array|string
     */
    public function getFieldValue();

    /**
     * @param string|array $fieldValue
     * @return void
     */
    public function setFieldValue($fieldValue): void;
}

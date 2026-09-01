<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

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
    public function setFieldValue(string $fieldValue): void;
}

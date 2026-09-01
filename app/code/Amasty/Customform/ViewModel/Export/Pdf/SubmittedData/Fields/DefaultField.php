<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Fields;

class DefaultField implements FieldValueInterface
{
    use FieldViewModelTrait;

    /**
     * @var string
     */
    private $fieldValue;
}

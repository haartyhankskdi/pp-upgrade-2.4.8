<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete;

use Amasty\Customform\Api\Data\FormInterface;

interface ProcessorInterface
{
    /**
     * @param FormInterface $form
     *
     * @return string[]
     */
    public function process(FormInterface $form): array;
}

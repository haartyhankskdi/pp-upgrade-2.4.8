<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Preparation;

interface PreparationInterface
{
    /**
     * @param array $formData
     * @return array
     */
    public function prepare(array $formData): array;
}

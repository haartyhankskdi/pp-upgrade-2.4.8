<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Form\Save\Preparation;

use Amasty\Customform\Api\Data\FormInterface;

class PrepareFormBuilderFields implements PreparationInterface
{
    public function prepare(array $formData): array
    {
        foreach ([FormInterface::FORM_JSON, FormInterface::FORM_TITLE] as $key) {
            if (empty($formData[$key])) {
                $formData[$key] = '[]';
            }
        }

        return $formData;
    }
}

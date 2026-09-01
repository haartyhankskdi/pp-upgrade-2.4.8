<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Form\Save\Preparation;

use Amasty\Customform\Api\Data\FormInterface;

class PrepareCustomerGroups implements PreparationInterface
{
    public function prepare(array $formData): array
    {
        $customerGroups = $formData[FormInterface::CUSTOMER_GROUP] ?? [];
        $formData[FormInterface::CUSTOMER_GROUP] = join(',', array_map('intval', $customerGroups));

        return $formData;
    }
}

<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\ViewModel\Customer;

interface CustomerInfoProviderInterface
{
    public function getCustomerId(): int;

    public function getCustomerGroupId(): int;
}

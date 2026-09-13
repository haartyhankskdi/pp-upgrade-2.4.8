<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store;

class Stores implements OptionSourceInterface
{
    /**
     * @var Store
     */
    private $storeProvider;

    public function __construct(
        Store $storeProvider
    ) {
        $this->storeProvider = $storeProvider;
    }

    public function toOptionArray()
    {
        return $this->storeProvider->getStoreValuesForForm(false, true);
    }
}

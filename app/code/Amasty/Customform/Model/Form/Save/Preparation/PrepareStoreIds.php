<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Save\Preparation;

use Amasty\Customform\Api\Data\FormInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\System\Store as SystemStore;

class PrepareStoreIds implements PreparationInterface
{
    /**
     * @var SystemStore
     */
    private $storeProvider;

    public function __construct(
        SystemStore $storeProvider
    ) {
        $this->storeProvider = $storeProvider;
    }

    public function prepare(array $formData): array
    {
        if ($this->shouldGetFirstAvailableStoreId($formData)) {
            $availableStore = $this->isAllStoreViewAvailable() ? 0
                : array_values($this->storeProvider->getStoreCollection())[0]->getStoreId();
        }

        $storeIds = isset($availableStore) ? [(string)$availableStore] : $formData[FormInterface::STORE_ID];
        $formData[FormInterface::STORE_ID] = join(',', $storeIds);

        return $formData;
    }

    private function shouldGetFirstAvailableStoreId(array $formData): bool
    {
        return empty($formData[FormInterface::STORE_ID]) || (!$this->isAllStoreViewAvailable()
            && $formData[FormInterface::STORE_ID] === [(string)Store::DEFAULT_STORE_ID]);
    }

    private function isAllStoreViewAvailable(): bool
    {
        return $this->storeProvider->getStoreValuesForForm(false, true)[0]['value'] === 0;
    }
}

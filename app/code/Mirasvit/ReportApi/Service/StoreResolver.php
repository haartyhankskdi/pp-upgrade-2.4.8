<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-report-api
 * @version   1.0.95
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\ReportApi\Service;

use Magento\Directory\Model\Currency;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\ReportApi\Api\RequestInterface;
use Mirasvit\ReportApi\Config\Type\Store;

class StoreResolver
{
    /** @var string|null */
    private static $baseCurrencyCode = null;

    /** @var StoreManagerInterface */
    private $storeManager;

    private const STORE_ID_COLUMNS = [
        'sales_order|store_id',
        'sales_order_item|store_id',
        'sales_invoice|store_id',
        'sales_creditmemo|store_id',
        'sales_shipment|store_id',
        'quote|store_id',
        'quote_item|store_id',
    ];

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    public function registerRequest(RequestInterface $request): void
    {
        self::$baseCurrencyCode = null;

        $currencyResolved = false;

        foreach ($request->getFilters() as $filter) {
            if (!in_array($filter->getColumn(), self::STORE_ID_COLUMNS, true)) {
                continue;
            }

            // Website-level options arrive as scalar "w_<id>" tokens; expand them back to
            // concrete store IDs and write the result back onto the filter so every
            // downstream consumer (the SQL WHERE clause, currency resolution) sees real
            // store IDs. Plain store-level values are normalised to ints unchanged.
            $storeIds = $this->expandStoreValue($filter->getValue());
            $filter->setValue($storeIds);

            if (!$currencyResolved && !empty($storeIds)) {
                self::$baseCurrencyCode = $this->resolveBaseCurrency($storeIds);
                $currencyResolved       = true;
            }
        }
    }

    /**
     * Normalise a store filter value to a list of concrete store IDs.
     *
     * A value may mix scalar store IDs and Website-level "w_<id>" tokens (see
     * {@see Store::WEBSITE_VALUE_PREFIX}); a website token expands to every store ID in
     * that website, matching what the option originally represented.
     *
     * The returned IDs are strings, matching what a store-level selection has always sent to the
     * filter (the option value is `$store->getId()`), so the SQL layer sees no change in type.
     *
     * @param string|int|array $value
     * @return string[]
     */
    private function expandStoreValue($value): array
    {
        $items    = is_array($value) ? $value : [$value];
        $storeIds = [];

        foreach ($items as $item) {
            if (is_string($item) && strpos($item, Store::WEBSITE_VALUE_PREFIX) === 0) {
                $websiteId = (int)substr($item, strlen(Store::WEBSITE_VALUE_PREFIX));

                try {
                    /** @var \Magento\Store\Model\Website $website */
                    $website = $this->storeManager->getWebsite($websiteId);
                } catch (NoSuchEntityException $e) {
                    // intentional no-op: degrade to no stores on a stale/deleted website id
                    // (the filter then matches nothing) rather than fataling the report.
                    continue;
                }

                foreach (array_keys($website->getStoreIds()) as $storeId) {
                    if ((int)$storeId > 0) {
                        $storeIds[] = (string)(int)$storeId;
                    }
                }
            } elseif ((int)$item > 0) {
                $storeIds[] = (string)(int)$item;
            }
        }

        return array_values(array_unique($storeIds));
    }

    public function isResolved(): bool
    {
        return self::$baseCurrencyCode !== null;
    }

    public function getCurrencyModel(): Currency
    {
        /** @var \Magento\Store\Model\Store $store */
        $store        = $this->storeManager->getStore(0);
        $currencyCode = self::$baseCurrencyCode ?? $store->getBaseCurrencyCode();

        /** @var Currency $currency */
        $currency = ObjectManager::getInstance()->create(Currency::class);

        return $currency->load($currencyCode);
    }

    public function getGlobalCurrencyModel(): Currency
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore(0);
        /** @var Currency $currency */
        $currency = ObjectManager::getInstance()->create(Currency::class);

        return $currency->load($store->getBaseCurrencyCode());
    }

    private function resolveBaseCurrency(array $storeIds): ?string
    {
        $baseCurrencyCode = null;

        foreach ($storeIds as $storeId) {
            /** @var \Magento\Store\Model\Store $store */
            $store         = $this->storeManager->getStore($storeId);
            $storeCurrency = $store->getBaseCurrencyCode();

            if ($baseCurrencyCode === null) {
                $baseCurrencyCode = $storeCurrency;
            } elseif ($baseCurrencyCode !== $storeCurrency) {
                return null;
            }
        }

        return $baseCurrencyCode;
    }
}

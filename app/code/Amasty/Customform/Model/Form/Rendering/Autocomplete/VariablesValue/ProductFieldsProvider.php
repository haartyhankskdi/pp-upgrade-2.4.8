<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue;

use Amasty\Customform\Helper\Messages;
use Amasty\Customform\Model\Utils\CustomerInfo;
use Amasty\Customform\Model\Utils\ProductRegistry;
use Magento\Customer\Model\Customer;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductFieldsProvider
{
    public const PRODUCT_FIELDS = [
        Messages::PRODUCT_URL,
        Messages::PRODUCT_PRICE,
        Messages::PRODUCT_FINAL_PRICE,
        Messages::PRODUCT_SKU,
        Messages::PRODUCT_NAME
    ];

    /**
     * @var string[]
     */
    private $acceptableVariables;

    /**
     * @var ProductRegistry
     */
    private $productRegistry;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        ProductRegistry $productRegistry,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        array $acceptableVariables = self::PRODUCT_FIELDS
    ) {
        $this->productRegistry = $productRegistry;
        $this->priceCurrency = $priceCurrency;
        $this->storeManager = $storeManager;
        $this->acceptableVariables = $acceptableVariables;
    }

    /**
     * Retrieve data executor checker
     *
     * @param string $variableName
     * @return bool
     */
    public function isCanRetrieve(string $variableName): bool
    {
        $product = $this->productRegistry->getProduct();

        return $product !== null && in_array($variableName, $this->getAcceptableVariables());
    }

    /**
     * @param string $variableName
     * @return string
     */
    public function getValue(string $variableName): string
    {
        $product = $this->productRegistry->getProduct();

        switch (sprintf("{%s}", $variableName)) {
            case Messages::PRODUCT_URL:
                $value = $product->getProductUrl();
                break;
            case Messages::PRODUCT_SKU:
                $value = $product->getSku();
                break;
            case Messages::PRODUCT_NAME:
                $value = $product->getName();
                break;
            case Messages::PRODUCT_PRICE:
                $product->setPriceCalculation(false);
                $value = $this->renderPriceValue((float) $product->getPrice());
                $product->setPriceCalculation(true);
                break;
            case Messages::PRODUCT_FINAL_PRICE:
                $value = $this->renderPriceValue((float) $product->getFinalPrice());
                break;
            default:
                $value = '';
        }

        return $value;
    }

    private function renderPriceValue(float $priceValue): string
    {
        if ($priceValue > 0) {
            $storeId = $this->storeManager->getStore()->getId();
            $priceValue = $this->priceCurrency->convertAndRound($priceValue);
            return $this->priceCurrency->getCurrency($storeId)
                ->formatPrecision($priceValue, PriceCurrencyInterface::DEFAULT_PRECISION, [], false);
        }

        return '';
    }

    private function getAcceptableVariables(): array
    {
        return array_map(function (string $variable) {
            return trim($variable, '{}');
        }, $this->acceptableVariables);
    }
}

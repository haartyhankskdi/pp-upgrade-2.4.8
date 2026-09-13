<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue;

use Amasty\Customform\Model\Form\Rendering\Autocomplete\VariablesValue\Retrievers\RetrieverInterface;
use Amasty\Customform\Model\Utils\ProductRegistry;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManager;

class ProductAttributeProvider implements ProviderInterface
{
    /**
     * @var ProductRegistry
     */
    private $productRegistry;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var RetrieverInterface
     */
    private $attributeValueRetriever;

    public function __construct(
        ProductRegistry $productRegistry,
        ProductResource $productResource,
        StoreManager $storeManager,
        EavConfig $eavConfig,
        RetrieverInterface $attributeValueRetriever
    ) {
        $this->productRegistry = $productRegistry;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
        $this->attributeValueRetriever = $attributeValueRetriever;
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

        return $product !== null && $this->getProductAttribute($variableName) !== null;
    }

    /**
     * Retrieve product attribute value
     *
     * @param string $variableName
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValue(string $variableName): string
    {
        $product = $this->productRegistry->getProduct();
        $productAttribute = $this->getProductAttribute($variableName);

        if ($product->hasData($productAttribute->getAttributeCode())) {
            $attributeValue = $product->getData($productAttribute->getAttributeCode());
        } else {
            $attributeValue = $this->productResource->getAttributeRawValue(
                $product->getId(),
                $productAttribute->getAttributeCode(),
                $this->storeManager->getStore()->getId()
            );
        }

        if (is_array($attributeValue)) {
            $attributeValue = implode(',', $attributeValue);
        }

        $result = $this->attributeValueRetriever->retrieve(
            $productAttribute,
            (string) $attributeValue
        );

        return (string) $result;
    }

    private function getAttributeCode(string $variableName): string
    {
        return str_replace('product_', '', $variableName);
    }

    private function getProductAttribute(string $variableName): ?AttributeInterface
    {
        $attributeCode = $this->getAttributeCode($variableName);
        try {
            $attribute = $this->eavConfig->getAttribute(
                ProductAttributeInterface::ENTITY_TYPE_CODE,
                $attributeCode
            );
        } catch (LocalizedException $e) {
            $attribute = null;
        }

        return $attribute->getAttributeId() ? $attribute : null;
    }
}

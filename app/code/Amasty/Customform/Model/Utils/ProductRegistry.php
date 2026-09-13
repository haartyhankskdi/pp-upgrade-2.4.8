<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Utils;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRegistry
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var int|null
     */
    private $productId = null;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Set product ID
     *
     * @param int|null $productId
     */
    public function setProductId(?int $productId = null): void
    {
        $this->productId = $productId;
    }

    /**
     * Retrieve product
     *
     * @return ProductInterface|null
     */
    public function getProduct(): ?ProductInterface
    {
        if ($this->productId === null) {
            return null;
        }

        try {
            $product = $this->productRepository->getById($this->productId);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        return $product;
    }
}

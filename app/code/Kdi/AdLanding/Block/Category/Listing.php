<?php

declare(strict_types=1);

namespace Kdi\AdLanding\Block\Category;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class Listing extends Template
{
    protected ProductRepositoryInterface $productRepository;
    protected Image $imageHelper;
    protected PriceHelper $priceHelper;

    public function __construct(
        Template\Context $context,
        ProductRepositoryInterface $productRepository,
        Image $imageHelper,
        PriceHelper $priceHelper,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->priceHelper = $priceHelper;

        parent::__construct($context, $data);
    }

    public function getProducts(): array
    {
        return [
            $this->productRepository->getById(9424),
            $this->productRepository->getById(9411)
        ];
    }

    public function getProductImageUrl($product): string
    {
        return $this->imageHelper
            ->init($product, 'product_page_image_large')
            ->getUrl();
    }

    public function formatPrice(float $price): string
    {
        return $this->priceHelper->currency(
            $price,
            true,  // include currency symbol
            false  // don't wrap in span
        );
    }
}

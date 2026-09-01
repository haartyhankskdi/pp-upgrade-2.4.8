<?php

namespace Haartyhanks\ProductPage\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;

class WeightlossComparison extends Template
{
    protected $productRepository;
    protected $imageHelper;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;

        parent::__construct($context, $data);
    }

    public function getProductId()
    {
        $product = $this->registry->registry('current_product');

        return $product ? (int)$product->getId() : null;
    }

    public function isAllowedProduct()
    {
        $allowedProductIds = [
            9221,
            // Add more product IDs here
        ];

        if ($this->getRequest()->getFullActionName() === 'categoryquestwl_index_index') {
            return true;
        }
        return in_array(
            $this->getProductId(),
            $allowedProductIds
        );

    }

    public function getTreatments()
    {
        return [
            [
                'name' => 'Mounjaro',
                'image' => $this->getProductImageById(9305),
                'ingredient' => 'Tirzepatide (GLP-1 & GIP)',
                'how_to_take' => 'Once weekly injection',
                'how_it_works' => 'Acts on two pathways to reduce hunger and food cravings.',
                'effectiveness' => '',
                'class' => 'weight-d-2'
            ],
            [
                'name' => 'Foundayo',
                'image' => $this->getProductImageById(9567),
                'ingredient' => 'Orforglipron (GLP-1)',
                'how_to_take' => 'Once daily tablet, at any time, with or without food',
                'how_it_works' => 'Acts on pathways to help you feel fuller and less hungry.',
                'effectiveness' => 'About 7-11% at 72 weeks, depending on dosage.',
                'class' => 'weight-d-3'
            ],
            [
                'name' => 'Wegovy Injections',
                'image' => $this->getProductImageById(9221),
                'ingredient' => 'Semaglutide (GLP-1)',
                'how_to_take' => 'Once weekly injection',
                'how_it_works' => 'Helps you feel fuller and less hungry, reduces food cravings.',
                'effectiveness' => 'About 15% at 68 weeks.',
                'class' => 'weight-d-3'
            ],
            [
                'name' => 'Wegovy Tablet',
                'image' => $this->getProductImageById(9746),
                'ingredient' => 'Semaglutide (GLP-1)',
                'how_to_take' => 'Once daily tablet after an 8 hour fast with up to 120ml of water; wait 30 minutes before food, drink or other tablets.',
                'how_it_works' => 'Helps you feel fuller for longer, reduces food cravings.',
                'effectiveness' => 'About 14% at 64 weeks.',
                'class' => 'weight-d-3'
            ],
            [
                'name' => 'Saxenda',
                'image' => $this->getProductImageById(8294),
                'ingredient' => 'Liraglutide (GLP-1)',
                'how_to_take' => 'Once daily injection',
                'how_it_works' => 'Helps you feel fuller and less hungry.',
                'effectiveness' => 'About 8% at 56 weeks.',
                'class' => 'weight-d-3'
            ],
            [
                'name' => 'Orlistat/Xenical',
                'image' => $this->getProductImageById(2703),
                'ingredient' => 'Orlistat',
                'how_to_take' => 'One capsule with each main meal containing fat, up to 3 times daily.',
                'how_it_works' => 'Reduces the amount of dietary fat absorbed by the body.',
                'effectiveness' => 'About 5-10% over 12 months.',
                'class' => 'weight-d-3'
            ],
            [
                'name' => 'Mysimba',
                'image' => $this->getProductImageById(9338),
                'ingredient' => 'Naltrexone & Bupropion',
                'how_to_take' => 'Tablets increased gradually to 2 tablets twice daily.',
                'how_it_works' => 'Acts on appetite and reward pathways to reduce hunger and cravings.',
                'effectiveness' => 'About 5-9% at 56 weeks.',
                'class' => 'weight-d-3'
            ]
        ];
    }

    public function getProductImageById($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);

            return $this->imageHelper
                ->init($product, 'product_base_image')
                ->getUrl();
        } catch (\Exception $e) {
            return '';
        }
    }


    public function getMediaUrl()
    {
        return $this->getUrl('media');
    }
}

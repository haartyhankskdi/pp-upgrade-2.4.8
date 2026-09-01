<?php

namespace Haartyhanks\Catalog\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Data extends AbstractHelper
{
    protected $_registry;
    protected $product;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product $product,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->_registry = $registry;
        $this->product = $product;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    /* Get Current Product */
    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product')->getId();
    }

    /* Get Current Product's Type */
    public function getProductType()
    {
        $productType = null;
        try {
            $productType = $this->productRepository->getById($this->getCurrentProduct())->getTypeId();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $productType;
    }

    /* Get Child Products */
    public function getChildProducts()
    {   
        $productType = $this->getProductType();
        if($productType=='configurable'){
            $configProduct = $this->product->load($this->getCurrentProduct());
            $children = $configProduct->getTypeInstance()->getUsedProducts($configProduct);
            return $children;
        }
        return array();
    }

    /* Get Label by option id */
    public function getLabelById($attributeCode,$optionId)
    {
        $product = $this->productFactory->create();
        $isAttributeExist = $product->getResource()->getAttribute($attributeCode); 
        $optionText = '';
        if ($isAttributeExist && $isAttributeExist->usesSource()) {
            $optionText = $isAttributeExist->getSource()->getOptionText($optionId);
        }
        return $optionText;
    }
}

?>
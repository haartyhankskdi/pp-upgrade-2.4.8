<?php

namespace Haartyhanks\CategoryQuest\Block;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;

class Index extends Template
{
    /**
     * Undocumented variable
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepositoryInterface;
    
    /**
     * @var Magento\Catalog\Model\CategoryFactory;
     */
    protected $_categoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param CategoryFactory $categoryFactory
     * @param Context $context
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepository $categoryRepository
     * @param array $data
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        Context $context,
        ProductRepositoryInterface $productRepositoryInterface,
        StoreManagerInterface $storeManager,
        CategoryRepository $categoryRepository,
        array $data = []
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->_storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
    }

    
    public function getCategoryProducts($categoryId)
    {
        $products = $this->_categoryFactory->create()->load($categoryId)->getProductCollection()->addAttributeToSelect('*');
        return $products;
    }

    public function getProductIds($products)
    {
        $ids = [];  
        foreach($products as $_product){
            array_push($ids, $_product->getId());
        }
        return $ids;
    }

    public function getProduct($id){
        return $this->productRepositoryInterface->getById($id);
    }

    public function getCategoryUrl($categoryId)
    {
        $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
        return $category->getUrl();
    }
}
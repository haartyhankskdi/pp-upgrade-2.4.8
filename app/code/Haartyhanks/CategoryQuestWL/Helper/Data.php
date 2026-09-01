<?php
declare(strict_types=1);

namespace Haartyhanks\CategoryQuestWL\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\RequestInterface;

class Data extends AbstractHelper 
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManagerInterface;

    /**
     * @var RequestInterface
     */
    protected $_requestInterface;
    
    /**
     * @var CategoryRepository
     */
    protected $_categoryRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Magento\Catalog\Model\CategoryFactory;
     */
    protected $_categoryFactory;

    /**
     * @var EncoderInterface 
     */
    protected $_jsonEncoder;

    /**
     * @var FormatInterface 
     */
    protected $_localeFormat;

    /**
     * @var ProductCategoryList
     */
    public $productCategory;

    /**
     * @param Registry $registry
     * @param Product $product
     * @param ProductFactory $productFactory
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param SessionManagerInterface $coreSession
     * @param CollectionFactory $collectionFactory
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        Registry $registry,
        Product $product,
        ProductFactory $productFactory,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        SessionManagerInterface $coreSession,
        CollectionFactory $collectionFactory,
        CategoryFactory $categoryFactory,
        FormatInterface $localeFormat,
        ProductCategoryList $productCategory,
        StoreManagerInterface $storeManagerInterface,
        RequestInterface $requestInterface,
        CategoryRepository $categoryRepository
    )
    {
        $this->_registry = $registry;
        $this->product = $product;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->_coreSession = $coreSession;
        $this->_collectionFactory = $collectionFactory;
        $this->_categoryFactory = $categoryFactory;
        $this->_localeFormat = $localeFormat;
        $this->productCategory = $productCategory;
        $this->_categoryRepository = $categoryRepository;
        $this->_storeManager = $storeManagerInterface;
        $this->_request = $requestInterface;

    }

    /* Get Current Product */
    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product')->getId();
    }

    /* Get Current ProductId */
    public function getCurrentProductId()
    {
        return $this->_request->getParam('product_id');
    }

    /* Load WL Current Product */
    public function getCurrentWLProduct()
    {
        $productId = $this->getCurrentProductId();
        return $this->product->load($productId);
    }

    /* Get Current Product */
    public function getCurrentCategory()
    {
        return $this->_registry->registry('current_category');
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

    public function getProduct($id){
        return $this->productRepository->getById($id);
    }

    public function setCatValueSession($val) 
    {
        $this->_coreSession->start();
        $this->_coreSession->setCatValue($val);
    }
    public function setProdValueSession($val) 
    {
        $this->_coreSession->start();
        $this->_coreSession->setProdValue($val);
    }

    public function getCatValueSession()
    {
        $this->_coreSession->start();
        return $this->_coreSession->getCatValue();
    }

    public function getProdValueSession()
    {
        $this->_coreSession->start();
        return $this->_coreSession->getProdValue();
    }

    public function unSetCatValue()
    {
        $this->_coreSession->start();
        $this->_coreSession->unsCatValue();
    }
    public function unSetProdValue()
    {
        $this->_coreSession->start();
        $this->_coreSession->unsProdValue();
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

    /**
     * get all the category id
     *
     * @param int $productId
     * @return array
     */
    public function getCategoryIds(int $productId)
    {
        $categoryIds = $this->productCategory->getCategoryIds($productId);
        $category = [];
        if ($categoryIds) {
            $category = array_unique($categoryIds);
        }
        return $category;
    }

    public function getCatUrlById($id){
        $_category=$this->_categoryRepository->get($id, $this->_storeManager->getStore()->getId());
        return $_category->getUrl();
    }

    public function getIsFilledCategory(){
        $this->_coreSession->start();
        return $this->_coreSession->getIsFilled();
    }
    public function unsIsFilledCategory(){
        $this->_coreSession->start();
        return $this->_coreSession->unsIsFilled();
    }

    public function getCurrentStoreId()
    {
       /* Get Current Store ID */
       return $this->_storeManager->getStore()->getId();
    }

    /**
     * Get Current Store Base URL
     *
     * @return string
     */
    public function getCurrentStoreBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getProductCollectionByCategories($cat_id)
    {
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addCategoriesFilter(['in' => $cat_id]);
        $collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('type_id', ['eq' => 'configurable']);
        $collection->joinField('stock_item', 'cataloginventory_stock_item', 'is_in_stock', 'product_id=entity_id', 'is_in_stock=1');
        $collection->addAttributeToSort('name', 'ASC');
        return $collection;
    }
}

?>
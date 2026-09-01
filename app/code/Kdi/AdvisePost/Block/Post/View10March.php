<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Kdi\AdvisePost\Block\Post;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Helper\Image;
use Magefan\Blog\Api\PostRepositoryInterface;
use Magefan\Blog\Api\CategoryRepositoryInterface as BlogCategoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Kdi\AdvisePost\Model\AdvicePostFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magefan\Blog\Model\ResourceModel\Post\CollectionFactory;


class View extends Template
{
    /** @var BlogCategoryRepositoryInterface */
    protected $blogCategoryRepository;

    /** @var Image */
    protected $imageHelper;

    /** @var PostRepositoryInterface */
    protected $postRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var RequestInterface */
    protected $request;

    /** @var AdvicePostFactory */
    protected $advicePostFactory;

    /** @var ProductHelper */
    protected $ProductHelper;

    /**
     * @var CollectionFactory
     */
    protected $postCollectionFactory;

    protected $_url;


    /**
     * Constructor
     *
     * @param Context $context
     * @param BlogCategoryRepositoryInterface $blogCategoryRepository
     * @param Image $imageHelper
     * @param PostRepositoryInterface $postRepository
     * @param CategoryRepository $categoryRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param AdvicePostFactory $advicePostFactory
     * @param ProductHelper $productHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $blogCategoryRepository,
        Image $imageHelper,
        PostRepositoryInterface $postRepository,
        CategoryRepository $categoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        AdvicePostFactory $advicePostFactory,
        ProductHelper $productHelper,
        CollectionFactory $postCollectionFactory,
        \Magefan\Blog\Model\Url $url,
        array $data = []
    ) {
        $this->blogCategoryRepository = $blogCategoryRepository;
        $this->imageHelper = $imageHelper;
        $this->postRepository = $postRepository;
        $this->categoryRepository = $categoryRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request = $request;
        $this->advicePostFactory = $advicePostFactory;
        $this->productHelper = $productHelper;
        $this->postCollectionFactory = $postCollectionFactory;
        $this->_url = $url;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve all posts related to the given model
     *
     * @return array
     */
    public function getAllPosts(): array
    {
       // Create a collection
        $collection = $this->postCollectionFactory->create();

        // Filter by category ID
        $collection->addFieldToFilter('category_id', ['finset' => 11]);

        // Filter by post status (if applicable)
        $collection->addFieldToFilter('is_active', 1);

        // Order by creation date (optional)
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }

    /**
     * Retrieve model data based on request parameters
     *
     * @return \Kdi\AdvisePost\Model\AdvicePost|false
     */
    public function getModelData()
    {
        $params = $this->request->getParams();
        if (!empty($params['id'])) {
            return $this->advicePostFactory->create()->load($params['id']);
        }
        return false;
    }

    /**
     * Retrieve product collection for the associated category
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|null
     */
    public function getProductCollection()
    {
        $model = $this->getModelData();
        if ($model) {
            $category = $this->categoryRepository->get($model->getCategoryId());
            $productCollection = $category->getProductCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE]);

            return $productCollection;
        }
        return null;
    }


    public function getImagePath($product){
       return $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl(); 
    }


    public function getProductUrl($product){
        return $this->productHelper->getProductUrl($product);
    }

    public function getQuery()
    {
        return urldecode($this->getRequest()->getParam('q', ''));
    }


    public function getFormUrl()
    {
        return $this->_url->getUrl('', \Magefan\Blog\Model\Url::CONTROLLER_SEARCH);
    }

}

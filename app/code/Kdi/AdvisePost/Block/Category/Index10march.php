<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Block\Category;

use Kdi\AdvisePost\Model\ResourceModel\AdvicePost\Collection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface ;

class Index extends \Magento\Framework\View\Element\Template
{


    protected $postCollection;

    protected $store;

    protected $catalog;

    protected $_url;



    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        StoreManagerInterface $store,
        CategoryFactory $categoryFactory,
        Collection $collection,
        \Magefan\Blog\Model\Url $url,
        array $data = []
    ) {
        $this->store = $store;
        $this->catalog = $categoryFactory;
        $this->collection = $collection;
        $this->_url = $url;
        parent::__construct($context, $data);
    }


    /**
     * 
     * 
     */
    public function getImagePath($categoryId){
        $category = $this->catalog->create()->load($categoryId);
        $store = $this->store->getStore()->getBaseUrl();
        $path = $store.$category->getImageUrl();
      //  $imagePath = str_replace("//", "/", $path);
        return $path;
    }


    public function getCollection(){
        $collection = $this->collection->load();
        return $collection;
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


<?php
/**
 * Copyright © no All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Kdi\AdvisePost\Block\Category;

use Kdi\AdvisePost\Model\ResourceModel\AdvicePost\Collection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\Blog\Model\ResourceModel\Category\Collection as CategoryCollection; 
use \Magento\Framework\DataObject\IdentityInterface;

class Index extends \Magento\Framework\View\Element\Template 
{


    protected $postCollection;

    protected $store;

    protected $catalog;

    protected $_url;

    protected $categoryCollection;



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
        CategoryCollection $categoryCollection,
        array $data = []
    ) {
        $this->store = $store;
        $this->catalog = $categoryFactory;
        $this->collection = $collection;
        $this->_url = $url;
        $this->categoryCollection = $categoryCollection;
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

    /**
     * Get grouped categories
     * @return \Magefan\Blog\Model\ResourceModel\Category\Collection
     */
    public function getGroupedChilds()
    {
        $k = 'grouped_childs';
        if (!$this->hasData($k)) {
            $array = $this->collection
                ->addActiveFilter()
                ->addStoreFilter($this->_storeManager->getStore()->getId())
                ->setOrder('position')
                ->getTreeOrderedArray();
            foreach ($array as $key => $item) {
                $maxDepth = $this->maxDepth();
                if ($maxDepth > 0 && $item->getLevel() >= $maxDepth) {
                    unset($array[$key]);
                }
            }

            $this->setData($k, $array);
        }

        return $this->getData($k);
    }


    public function getBlogCategory(){

    $categories = [
        ["url" => "/blog/category/high-blood-pressure", "label" => "High Blood Pressure"],
        ["url" => "/blog/category/acid-reflux", "label" => "Acid Reflux"],
        ["url" => "/blog/category/allergy", "label" => "Allergy"],
        ["url" => "/blog/category/oral-contraceptive", "label" => "Contraceptive"],
        ["url" => "/blog/category/diabetes", "label" => "Diabetes"],
        ["url" => "/blog/category/acne-rosacea", "label" => "Acne/Rosacea"],
        ["url" => "/blog/category/hair-loss", "label" => "Hair Loss"],
        ["url" => "/blog/category/hrt", "label" => "HRT"],
        ["url" => "/blog/category/quit-smoking", "label" => "Quit Smoking"],
        ["url" => "/blog/category/prescription-medicines", "label" => "Prescription Medicines"],
        ["url" => "/blog/category/migraine", "label" => "Migraine Treatment"],
        ["url" => "/blog/category/psoriasis-and-eczema", "label" => "Psoriasis and Eczema"],
        ["url" => "/blog/category/genital-warts", "label" => "Genital Warts"],
        ["url" => "/blog/category/haemorrhoids", "label" => "Haemorrhoids"],
        ["url" => "/blog/category/asthma", "label" => "Asthma/COPD"],
        ["url" => "/blog/category/flu", "label" => "Flu"],
        ["url" => "/blog/category/cystitis", "label" => "Cystitis"],
        ["url" => "/blog/category/covid-19", "label" => "Covid-19"],
        ["url" => "/blog/category/jet-lag", "label" => "Jet lag"],
        ["url" => "/blog/category/erectile-dysfunction", "label" => "Erectile Dysfunction"],
        ["url" => "/blog/category/cholesterol", "label" => "High Cholesterol"],
        ["url" => "/blog/category/general-healths", "label" => "General Healths"],
        ["url" => "/blog/category/period-delay", "label" => "Period Delay"],
        ["url" => "/blog/category/weight-loss-treatment", "label" => "Weight Loss"],
        ["url" => "/blog/category/anti-malarial", "label" => "Anti-Malarial"],
        ["url" => "/blog/category/eyeearnose-treatment", "label" => "Eye/Ear/Nose"],
        ["url" => "/blog/category/thrush", "label" => "Thrush"],
        ["url" => "/blog/category/heart-attack", "label" => "Heart Attack"],
        ["url" => "/blog/category/anti-inflammatory", "label" => "Anti-inflammatories"],
        ["url" => "/blog/category/gout-treatment", "label" => "Gout"],
        ["url" => "/blog/category/underactive-thyroid-treatment", "label" => "Underactive Thyroid"],
        ["url" => "/blog/category/high-cholesterol", "label" => "High Cholesterol"],
        ["url" => "/blog/category/morning-after-pills", "label" => "Morning After Pill"],
        ["url" => "/blog/category/hirsutism", "label" => "Hirsutism"],
        ["url" => "/blog/category/wound-dressing", "label" => "Wound Dressing"],
        ["url" => "/blog/category/osteoporosis-treatment", "label" => "Osteoporosis"],
        ["url" => "/blog/category/vitamin", "label" => "Vitamins"],
        ["url" => "/blog/category/bacterial-vaginosis-bv", "label" => "Bacterial Vaginosis BV"],
        ["url" => "/blog/category/blood-glucose-testing-strips", "label" => "Blood Glucose Test Strips"],
        ["url" => "/blog/category/diabetes-tablets", "label" => "Diabetes Tablets"],
        ["url" => "/blog/category/diabetes-injections", "label" => "Diabetes Injections"],
    ];
    return $categories;

    }




}


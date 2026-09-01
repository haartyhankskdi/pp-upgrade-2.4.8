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
use Magento\Framework\Registry;
use Magefan\Blog\Model\Url;

class View extends Template
{
    protected BlogCategoryRepositoryInterface $blogCategoryRepository;
    protected Image $imageHelper;
    protected PostRepositoryInterface $postRepository;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected CategoryRepository $categoryRepository;
    protected RequestInterface $request;
    protected AdvicePostFactory $advicePostFactory;
    protected ProductHelper $productHelper;
    protected CollectionFactory $postCollectionFactory;
    protected Url $_url;
    protected Registry $registry;

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
        Url $url,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
        $this->registry = $registry;
    }

    public function getCurrentCategoryId(): ?int
    {
        $category = $this->registry->registry('current_blog_category');
        return $category ? (int) $category->getId() : null;
    }

    public function getAllPosts(): array
    {
        return $this->postCollectionFactory->create()
            ->addFieldToFilter('category_id', ['finset' => 11])
            ->addFieldToFilter('is_active', 1)
            ->setOrder('created_at', 'DESC')
            ->getItems();
    }

    public function getModelData(): ?\Kdi\AdvisePost\Model\AdvicePost
    {
        $id = $this->request->getParam('id');
        return $id ? $this->advicePostFactory->create()->load($id) : null;
    }

    public function getProductCollection(): ?\Magento\Catalog\Model\ResourceModel\Product\Collection
    {
        $blogCategoryId = $this->getCurrentCategoryId();
        foreach ($this->getCategoryIdByIdentifier() as $category) {
            if ($category['id'] === $blogCategoryId) {
                $categoryModel = $this->categoryRepository->get($category['catalog_id']);
                return $categoryModel->getProductCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                    ->addAttributeToFilter('visibility', ['neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])->setPageSize(3);
            }
        }
        return null;
    }

    public function getBlogDetails(){

        $category = $this->getCurrentCategoryId();
        $data = $this->blogCategoryRepository->getById($category);
        return $data;
        echo "<pre>";
        print_r($data->getData());

        exit();
    }

    public function getImagePath($product): string
    {
        return $this->imageHelper->init($product, 'product_thumbnail_image')->getUrl();
    }

    public function getProductUrl($product): string
    {
        return $this->productHelper->getProductUrl($product);
    }

    public function getQuery(): string
    {
        return urldecode($this->getRequest()->getParam('q', ''));
    }

    public function getFormUrl(): string
    {
        return $this->_url->getUrl('', Url::CONTROLLER_SEARCH);
    }

    public function getCategoryIdByIdentifier(){
        return [
            ["id" => 1, "identifier" => "high-blood-pressure" , "catalog_id" => 96 ],
            ["id" => 2, "identifier" => "acid-reflux" ,"catalog_id" => 168 ],
            ["id" => 3, "identifier" => "allergy" ,"catalog_id" => 87 ],
            ["id" => 4, "identifier" => "oral-contraceptive" ,"catalog_id" => 96 ],
            ["id" => 5, "identifier" => "diabetes" ,"catalog_id" => 160 ],
            ["id" => 6, "identifier" => "acne-rosacea" ,"catalog_id" => 82 ],
            ["id" => 7, "identifier" => "hair-loss","catalog_id" => 83 ],
            ["id" => 8, "identifier" => "hrt","catalog_id" => 104 ],
            ["id" => 9, "identifier" => "quit-smoking","catalog_id" => 85 ],
            ["id" => 10, "identifier" => "prescription-medicines","catalog_id" => 79 ],
            ["id" => 11, "identifier" => "migraine","catalog_id" => 92 ],
            ["id" => 12, "identifier" => "psoriasis-and-eczema","catalog_id" => 106 ],
            ["id" => 13, "identifier" => "genital-warts","catalog_id" => 162 ],
            ["id" => 14, "identifier" => "haemorrhoids","catalog_id" => 108 ],
            ["id" => 15, "identifier" => "asthma","catalog_id" => 89 ],
            ["id" => 16, "identifier" => "flu","catalog_id" => 91 ],
            ["id" => 17, "identifier" => "cystitis","catalog_id" => 90 ],
            ["id" => 19, "identifier" => "covid-19","catalog_id" => 121 ],
            ["id" => 20, "identifier" => "jet-lag","catalog_id" => 99 ],
            ["id" => 21, "identifier" => "erectile-dysfunction","catalog_id" => 81 ],
            ["id" => 22, "identifier" => "cholesterol","catalog_id" => 95 ],
            ["id" => 23, "identifier" => "general-healths","catalog_id" => 123 ],
            ["id" => 24, "identifier" => "period-delay","catalog_id" => 181 ],
            ["id" => 25, "identifier" => "weight-loss-treatment","catalog_id" => 84 ],
            ["id" => 26, "identifier" => "stop-smoking","catalog_id" => 85 ],
            ["id" => 27, "identifier" => "anti-malarial","catalog_id" => 88 ],
            ["id" => 28, "identifier" => "eyeearnose-treatment","catalog_id" => 140 ],
            ["id" => 29, "identifier" => "flu-2","catalog_id" => 91 ],
            ["id" => 30, "identifier" => "thrush","catalog_id" => 141 ],
            ["id" => 31, "identifier" => "heart-attack","catalog_id" => 96 ],
            ["id" => 32, "identifier" => "anti-inflammatory","catalog_id" => 142 ],
            ["id" => 33, "identifier" => "gout-treatment","catalog_id" => 145 ],
            ["id" => 34, "identifier" => "underactive-thyroid-treatment","catalog_id" => 147 ],
            ["id" => 35, "identifier" => "high-cholesterol","catalog_id" => 95 ],
            ["id" => 36, "identifier" => "morning-after-pills" ,"catalog_id" => 98 ],
            ["id" => 37, "identifier" => "hirsutism" ,"catalog_id" => 101 ],
            ["id" => 38, "identifier" => "wound-dressing", "catalog_id" => 149],
            ["id" => 39, "identifier" => "osteoporosis-treatment","catalog_id" => 143],
            ["id" => 40, "identifier" => "vitamin","catalog_id" => 148],
            ["id" => 41, "identifier" => "bacterial-vaginosis-bv","catalog_id" => 107],
            ["id" => 42, "identifier" => "blood-glucose-testing-strips","catalog_id" => 151],
            ["id" => 43, "identifier" => "diabetes-tablets","catalog_id" => 160],
            ["id" => 44, "identifier" => "diabetes-injections","catalog_id" => 161],
        ];

    }

    public function getCateDesc($id){

     $data = [
        25 => "Struggling with weight management? Our team is here to provide expert advice and personalised solutions to help you reach your health goals. Book a consultation today to get tailored guidance and start your journey towards a healthier lifestyle.",
        44 => "Struggling with diabetes management? Our team can provide expert advice on diabetes injections and help you take control of your health. Book a consultation today to get tailored guidance and the best treatment options for your needs.",
        11 => "From lifestyle advice to medical treatments, we offer professional support for all aspects of men's health. Book a consultation with our specialists today to discuss your health concerns and find the right solutions.",
        21 => "Don’t let erectile dysfunction affect your confidence or quality of life. Our experts offer discreet and effective advice to help manage this condition. Schedule your consultation now to explore treatment options that work for you.",
        7 => "Experiencing hair loss? We provide personalised advice on treatments that can help. Book a consultation with our team to discuss your options and start your journey to regaining confidence in your appearance.",
        13 => "If you're dealing with genital warts, we're here to help with discreet and professional advice. Our experts can guide you through treatment options and offer support. Book your consultation now for personalised care and effective solutions.",
        1 => "Managing high blood pressure is crucial for your overall health. Our expert team can provide personalised advice and treatment options to help you keep your blood pressure under control. Book a consultation today and take the first step towards better heart health.",
        2 => "If you're struggling with acid reflux, our team offers advice on managing symptoms and finding effective treatments. Book a consultation today to get personalised guidance and start living without the discomfort of acid reflux.",
        3 => "Allergies can disrupt daily life, but with the right support, they can be managed effectively. Our experts are here to provide advice on allergy treatments and solutions. Schedule a consultation today to get the relief you need.",
        4 => "Choosing the right contraceptive method is important for your health and well-being. Our team can offer expert advice on the best options for you. Book a consultation today to discuss your choices and make an informed decision.",
        6 => "If you're dealing with acne or rosacea, we can help with effective treatments and expert advice. Book a consultation today to discuss your options and start your journey to clearer skin.",
        8 => "Hormone Replacement Therapy (HRT) can help manage symptoms related to menopause and hormonal imbalances. Our experts are here to offer guidance on HRT options and help you make the right choice. Book a consultation today to learn more.",
        9 => "Ready to quit smoking? Our experts are here to help you take control of your health. With personalised advice and proven strategies, we can support you through every step of your journey. Book a consultation today and start your path to a smoke-free life.",
        10 => "Need advice on prescription medicines? Our team can guide you through your medication options and ensure you're taking the right treatment for your needs. Book a consultation to discuss your prescriptions and get expert guidance.",
        11 => "If you're suffering from migraines, we offer professional advice on effective treatment options. Our experts can help you manage your condition and find relief. Book a consultation today to explore the best solutions for your migraines.",
        12 => "Dealing with psoriasis or eczema? Our team can provide personalised advice and treatment options to help manage your skin condition. Book a consultation today for expert guidance and effective solutions to soothe your skin.",
        14 =>"Suffering from haemorrhoids? We can offer professional advice on managing symptoms and finding effective treatments. Book a consultation today to explore your options and start feeling better.",
        15 => "Managing asthma or COPD is crucial to maintaining your quality of life. Our experts can provide guidance on the right treatments and strategies to help you breathe easier. Book a consultation today to discuss your respiratory health.",
        16 => "Feeling unwell with flu symptoms? Our team is here to offer advice on managing the flu and finding the right treatments to help you recover. Book a consultation today and get the support you need.",
        17 => "Dealing with cystitis? Our experts can offer advice on effective treatments and how to manage the condition. Book a consultation today for personalised care and find the relief you need.",
        19 => "If you're concerned about Covid-19, we can provide up-to-date advice and support on prevention, symptoms, and treatment. Book a consultation today for guidance on staying healthy during the pandemic.",
        20 => "Struggling with jet lag after travel? Our experts can provide tips and treatments to help you recover faster and get back to feeling like yourself. Book a consultation today for personalised advice on overcoming jet lag.",
        22 => "Managing high cholesterol is essential for heart health. Our team can offer advice on lifestyle changes and treatments to help you control your cholesterol levels. Book a consultation today for personalised guidance on managing your cholesterol.",
        23 => "For any general health concerns, our team is here to help with expert advice and tailored solutions. Whether you're looking for preventative care or treatment options, book a consultation today and take a proactive approach to your health.",
        24 => "If you're experiencing a delay in your period, our experts can provide advice on potential causes and treatment options. Book a consultation today to discuss your symptoms and find the right solutions.",
        27 => "Travelling abroad? Our experts can provide advice on the best antimalarial medications to protect your health. Book a consultation today to ensure you're prepared and stay safe during your travels.",
        28 => "If you're experiencing issues with your eyes, ears, or nose, our team can provide professional advice and treatment options. Book a consultation today for tailored solutions to address your concerns.",
        30 => "Dealing with thrush? We can offer discreet advice and effective treatments to help you manage the condition. Book a consultation today for professional care and support.",
        31 => "A heart attack can be a life-changing event, and managing your heart health is crucial. Our experts provide advice on recovery, prevention, and lifestyle changes. Book a consultation today for personalised support and guidance on heart health.",
        32 => "Anti-inflammatory medications can help manage pain and inflammation. Our team can guide you on the best options for your condition and ensure you're using them safely. Book a consultation today to discuss your needs and find the right solution.",
        33 => "Suffering from gout? Our experts can provide advice on managing symptoms and preventing flare-ups. Book a consultation today for personalised care and effective treatment options.",
        34 => "An underactive thyroid can affect your energy levels and overall health. Our experts offer guidance on managing thyroid function through medication and lifestyle changes. Book a consultation today to get your thyroid under control.",
        35 => "High cholesterol can increase the risk of heart disease. Our team can offer personalised advice on how to manage and lower your cholesterol levels. Book a consultation today to discuss your treatment options and take control of your heart health.",
        36 => "If you need the morning-after pill, our team can provide advice on its use and ensure you get the support you need. Book a consultation today for discreet, professional care.",
        37 => "Hirsutism, or excess hair growth, can affect your confidence. Our experts offer advice on treatment options to help manage the condition. Book a consultation today for tailored solutions.",
        38 => "If you need advice on wound care or dressing, our team can guide you through the best options for healing. Book a consultation today to ensure your wound is treated properly for faster recovery.",
        39 => "Osteoporosis can weaken bones and increase the risk of fractures. Our team can help with advice on prevention, treatment, and managing the condition. Book a consultation today to keep your bones healthy and strong.",
        40 => "Vitamins are essential for overall health. Our experts can guide you on the right vitamins and supplements for your needs. Book a consultation today to ensure you're meeting your nutritional requirements.",
        41 => "If you're dealing with bacterial vaginosis, our experts can provide advice on treatment options to restore balance. Book a consultation today for discreet, professional care.",
        42 => "Managing diabetes involves regular monitoring of blood glucose levels. We can offer advice on the best test strips for your needs. Book a consultation today to discuss your diabetes management options.",
        5 => "Get expert advice on managing diabetes with personalised support on diet, lifestyle, and medication. Book a consultation to take control of your blood sugar and live healthier every day.",
        43 => "Need help with your diabetes tablets? Our experts guide you on dosage, side effects, and effectiveness. Book a consultation today to ensure your medication supports your health goals."
       

        ];

        return $data[$id] ?? null; // return description or null if not found
    }
}

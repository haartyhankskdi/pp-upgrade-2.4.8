<?php
namespace Kdi\Consultation\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Kdi\Consultation\Helper\CustomSession;
use Haartyhanks\CategoryQuest\Helper\Data;
use Magento\Framework\Session\SessionManagerInterface as Session;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductViewPlugin
{
    protected $request;
    protected $session;
    protected $CustomSession;
    protected $HelperData;
    protected $productRepository;

    /**
     * @var Session
     */
    protected $sessionManager;

    public function __construct(
        RequestInterface $request,
        SessionManagerInterface $session,
        CustomSession $CustomSession,
        Data $data,
        ProductRepositoryInterface $productRepository
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->CustomSession = $CustomSession;
        $this->HelperData = $data;
        $this->productRepository = $productRepository;
        // $this->sessionManager = $sessionManager;
    }

    public function beforeExecute(\Magento\Catalog\Controller\Product\View $subject)
    {
        $productId = (int)$this->request->getParam('id');

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/products.log');
        $zendLogger = new \Zend_Log();
        $zendLogger->addWriter($writer);
        $zendLogger->info(" ------------ params ---------- " . print_r($this->request->getParams(), true));

        $category = $this->getProductCategories($productId);

        $zendLogger->info(" ------------ category ---------- " . print_r( $category , true));


       $isFilled = array(
            'category_questions_filled' => true,
            'category_id' => $category
        );
        $this->setIsFilledCategory($isFilled);
        //$this->HelperData->setCatValueSession($data);
        if ($productId) {
             $this->CustomSession->set($productId);
        }
    }


    public function setIsFilledCategory($values){
        $this->session->start();
        return $this->session->setIsFilled($values);
    }

    public function getProductCategories($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
            $categoryIds = $product->getCategoryIds();
            
            $categories = [];
            foreach ($categoryIds as $categoryId) {
                // You can load the category object if you need more details like name, URL etc.
                // $category = $this->_categoryRepository->get($categoryId);
                // $categories[] = $category;
                $categories[] = $categoryId; // For just category IDs
            }
            return $categories[0];

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Handle case where product is not found
            return [];
        }
    }
}

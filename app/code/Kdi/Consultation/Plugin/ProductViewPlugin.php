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
        $category = $this->getProductCategories($productId);
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
                $categories[] = $categoryId; // For just category IDs
            }
            return $categories[0];

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return [];
        }
    }
}

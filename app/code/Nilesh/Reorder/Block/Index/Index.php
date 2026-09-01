<?php
declare(strict_types=1);

namespace Nilesh\Reorder\Block\Index;

class Index extends \Magento\Framework\View\Element\Template
{

    protected $resultPageFactory;
    protected $request;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository      = $orderRepository;
        $this->_productRepository   = $productRepository;
        $this->request              = $request;
        $this->imageHelperFactory   = $imageHelperFactory;
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        $orderId = $this->request->getParam('order_id');
        return $this->getUrl('sales/order/reorder', ['order_id' => $orderId]);
    }

    public function getOrder()
    {
        $orderId = $this->request->getParam('order_id');
        // echo $orderId; exit();
        return $this->orderRepository->get($orderId);
    }

    public function getProductUrl($product_id = null)
    {
        $_product = $this->_productRepository->getById($product_id);
        return $_product;
    }

    public function getProductHelper($_product)
    {
        if($_product->getImage() && $_product->getImage() != 'no_selection'){
            return $this->imageHelperFactory->create()->init($_product, 'product_page_image_small')->getUrl();
        }else{
            return $this->imageHelperFactory->create()->getDefaultPlaceholderUrl('image');
        }
    }
}
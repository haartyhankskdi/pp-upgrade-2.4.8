<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Reorder\Block\Adminhtml;

class Email extends \Magento\Backend\Block\Template
{

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository      = $orderRepository;
        $this->_productRepository   = $productRepository;
        $this->imageHelperFactory   = $imageHelperFactory;
    }

    /**
     * @return string
     */
    public function getItemList($orderId)
    {
       return $this->orderRepository->get($orderId);
    }

    public function getProductUrl($product_id = null)
    {
        $_product = $this->_productRepository->getById($product_id);
        return $_product;
    }

    public function getProductImage($_product)
    {
        // return $this->imageHelperFactory->create()->init($_product, 'product_page_image_small')->getUrl();
        return $this->imageHelperFactory->create()->init($_product, 'product_page_image_medium')->getUrl();
    }

    public function getStoreBaseUrl($storeId = "")
    {
        return $this->_storeManager->getStore($storeId)->getBaseUrl();
    }
}
<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GoogleTagManager\Observer;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\GoogleTagManager\Helper\Data as HelperData;

/**
 * Class AddToWishlist
 * @package Mageplaza\GoogleTagManager\Observer
 */
class AddToWishlist implements ObserverInterface
{
    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @param HelperData $helper
     * @param ProductFactory $productFactory
     */
    public function __construct(
        HelperData $helper,
        ProductFactory $productFactory
    ) {
        $this->helper         = $helper;
        $this->productFactory = $productFactory;
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->isEnabled()) {
            $items = $observer->getData('items');
            foreach ($items as $item) {
                $product  = $item->getProduct();
                $quantity = $item->getData('qty');
                $this->setFBAddToWishlist($product, $quantity);
            }
        }

        return $this;
    }

    /**
     * @param $product
     * @param $quantity
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setFBAddToWishlist($product, $quantity)
    {
        $productData = $this->helper->getFBAddToWishlistData($product, $quantity);
        if ($this->helper->getSessionManager()->getPixelAddToWishlistData()) {
            $data   = $this->helper->getSessionManager()->getPixelAddToWishlistData();
            $status = true;
            foreach ($data['contents'] as $key => $value) {
                if ($product->getId() === $value['id']) {
                    $status                             = false;
                    $data['contents'][$key]['quantity'] += $quantity;
                }
            }
            if ($status) {
                $data['content_ids'][]  = $productData['id'];
                $data['content_name'][] = $productData['name'];
                $data['value']          += (float) $productData['price'] * $quantity;
                $data['contents'][]     = $productData;
            }
        } else {
            $data = [
                'content_ids'  => [$productData['id']],
                'content_name' => [$productData['name']],
                'content_type' => 'product',
                'contents'     => [$productData],
                'currency'     => $this->helper->getCurrentCurrency(),
                'value'        => (float) $productData['price'] * $quantity
            ];
        }
        $this->helper->getSessionManager()->setPixelAddToWishlistData($data);
    }
}

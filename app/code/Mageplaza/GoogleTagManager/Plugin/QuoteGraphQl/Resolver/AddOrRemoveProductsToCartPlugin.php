<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
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

namespace Mageplaza\GoogleTagManager\Plugin\QuoteGraphQl\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Mageplaza\GoogleTagManager\Helper\Data;

/**
 * Class AddOrRemoveProductsToCartPlugin
 * @package Mageplaza\GoogleTagManager\Plugin\QuoteGraphQl\Resolver
 */
class AddOrRemoveProductsToCartPlugin
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * AddOrRemoveProductsToCartPlugin constructor.
     *
     * @param Data $helperData
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        Data $helperData,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->helperData         = $helperData;
        $this->quoteRepository    = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @param ResolverInterface $subject
     * @param callable $proceed
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return mixed
     */
    public function aroundResolve(
        ResolverInterface $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $cartItem = [];

        if ($this->helperData->isEnabled()) {
            if (isset($args['input']['cart_item_id'])) {
                try {
                    /** @var $quoteIdMask QuoteIdMask */
                    $quoteIdMask = $this->quoteIdMaskFactory->create()->load($args['input']['cart_id'], 'masked_id');
                    /** @var Quote $quote */
                    $quote      = $this->quoteRepository->getActive($quoteIdMask->getQuoteId());
                    $quoteItem  = $quote->getItemById($args['input']['cart_item_id']);
                    $cartItem[] = [
                        'sku'      => $quoteItem->getSku(),
                        'quantity' => $quoteItem->getQty()
                    ];
                } catch (Exception $e) {
                    $cartItem = [];
                }
            }

            if (isset($args['input']['cart_items'])) {
                $cartItem = $args['input']['cart_items'];
            }

            if (isset($args['cartItems'])) {
                $cartItem = $args['cartItems'];
            }
        }

        $resolvedValue = $proceed($field, $context, $info, $value, $args);

        if (!empty($cartItem)) {
            $resolvedValue['cart']['cart_items'] = $cartItem;
            $resolvedValue['cart']['is_remove']  = isset($args['input']['cart_item_id']);
        }

        return $resolvedValue;
    }
}

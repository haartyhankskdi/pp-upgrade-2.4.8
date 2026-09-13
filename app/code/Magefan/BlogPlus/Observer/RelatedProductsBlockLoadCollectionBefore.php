<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RelatedProductsBlockLoadCollectionBefore implements ObserverInterface
{
    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $collection = $observer->getData('collection');
        if ($collection->isLoaded()) {
            return;
        }

        $post = $observer->getData('block')->getPost();
        if ($post) {
            $postId = (int)$post->getId();
            $rprTable = $collection->getResource()->getTable('magefan_blog_post_relatedproduct_by_rule');

            $collection->getSelect()->joinLeft(
                ['rpr' => $rprTable],
                "rpr.product_id = e.entity_id AND rpr.post_id = $postId AND rpr.store_id = 0",
                []
            );
            /*
            $storeId = (int)$collection->getStoreId();
            $collection->getSelect()->joinLeft(
                ['rpr2' => $rprTable],
                "rpr2.product_id = e.entity_id AND rpr2.post_id = $postId AND rpr2.store_id = $storeId",
                []
            );
            */


            $where = $collection->getSelect()->getPart('where');
            
            foreach ($where as $key => $part) {
                if (strpos($part, 'rl.post_id') !== false) {
                    unset($where[$key]);
                }
            }

            foreach ($where as $key => $part) {
                foreach (['AND', 'OR', 'XOR', '&&', '||', '&', '|'] as $sqlOperator) {
                    if (0 === mb_stripos($part, $sqlOperator)) {
                        $part = mb_substr($part, mb_strlen($sqlOperator));
                        $where[$key] = $part;
                        break;
                    }
                }
                break;
            }

            $collection->getSelect()->setPart('where', array_values($where));

            //$collection->getSelect()->where('rl.post_id = ? OR rpr.post_id = ? OR rpr2.post_id = ?', $postId);
            $collection->getSelect()->where('rl.post_id = ? OR rpr.post_id = ? ', $postId);
        }

        $collection->getSelect()
            ->where('display_on_post = 0 OR display_on_post IS NULL');
    }
}

<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Block\Catalog\Product;

use Magefan\Blog\Block\Catalog\Product\RelatedPosts;
use Magefan\Blog\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class RelatedPostsPlugin
{
    /**
     * Related posts on product page enabled
     */
    public const XML_PRODUCT_PAGE_RELATED_POSTS_ENABLED = 'mfblog/product_page/related_posts_enabled';

    /**
     * Include post rich snippet on product page
     */
    public const XML_PRODUCT_PAGE_INCLUDE_POST_RICH_SNIPPET = 'mfblog/product_page/include_post_rich_snippet';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Add display_on_product column to post collection
     *
     * @param RelatedPosts $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetPostCollection(RelatedPosts $subject, $result)
    {
        $collection = $result;
        if ($collection->isLoaded()) {
            return $result;
        }

        $product = $subject->getProduct();
        if ($product) {
            $productId = (int)$product->getId();
            $rprTable = $collection->getResource()->getTable('magefan_blog_post_relatedproduct_by_rule');

            $collection->getSelect()->joinLeft(
                ['rpr' => $rprTable],
                "rpr.post_id = main_table.post_id AND rpr.product_id = $productId AND rpr.store_id = 0",
                []
            );
            /*
            $storeId = (int)$this->storeManager->getStore()->getId();
            $collection->getSelect()->joinLeft(
                ['rpr2' => $rprTable],
                "rpr2.post_id = main_table.post_id AND rpr2.product_id = $productId AND rpr2.store_id = $storeId",
                []
            );
            */

            $where = $collection->getSelect()->getPart('where');
            foreach ($where as $key => $part) {
                if (strpos($part, 'rl.related_id') !== false) {
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

            //$collection->getSelect()->where('rl.related_id = ? OR rpr.product_id = ? OR rpr2.product_id = ?', $productId);
            $collection->getSelect()->where("rl.related_id = $productId OR rpr.product_id = $productId");
        }

        $collection->getSelect()
            ->where('display_on_product = 0 OR display_on_product IS NULL');
        return $collection;
    }

    /**
     * Add related posts rich snippets to product page
     *
     * @param RelatedPosts $subject
     * @param string|null $result
     * @return string
     * @throws LocalizedException
     */
    public function afterToHtml(RelatedPosts $subject, ?string $result): ?string
    {
        if ($result && $this->isProductPageRelatedPostsEnabled()
            && $this->isProductPageRichSnippetEnabled()
        ) {
            if ($subject->getRichSnippetAdded()) {
                return $result;
            }
            $subject->setRichSnippetAdded(true);

            $snippetsHtml = '';
            foreach ($subject->getPostCollection() as $post) {
                $snippetsHtml .= $subject->getLayout()->createBlock(\Magefan\Blog\Block\Post\View\Richsnippets::class)
                    ->setPost($post)
                    ->toHtml();
            }

            $result .= $snippetsHtml;
        }

        return $result;
    }

    /**
     * Check if related posts on product page is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isProductPageRelatedPostsEnabled($storeId = null): bool
    {
        return $this->config->isEnabled($storeId) && (bool)$this->config->getConfig(
            self::XML_PRODUCT_PAGE_RELATED_POSTS_ENABLED,
            $storeId
        );
    }

    /**
     * Check if post rich snippet on product page is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isProductPageRichSnippetEnabled($storeId = null): bool
    {
        return $this->config->isEnabled($storeId) && (bool)$this->config->getConfig(
            self::XML_PRODUCT_PAGE_INCLUDE_POST_RICH_SNIPPET,
            $storeId
        );
    }
}

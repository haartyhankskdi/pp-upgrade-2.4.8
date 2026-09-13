<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model;

/**
 * Magefan Blog Config Model
 */
class Config extends \Magefan\Blog\Model\Config
{
    public const XML_AUTO_RELATED_POSTS_ENABLED        = 'mfblog/post_view/related_posts/autorelated_enabled';
    public const XML_AUTO_RELATED_POSTS_BLACK_WORDS    = 'mfblog/post_view/related_posts/autorelated_black_words';
    public const XML_AUTO_RELATED_PRODUCTS_ENABLED     = 'mfblog/post_view/related_products/autorelated_enabled';
    public const XML_AUTO_RELATED_PRODUCTS_BLACK_WORDS = 'mfblog/post_view/related_products/autorelated_black_words';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var array|null
     */
    protected $autoRelatedBlackWords;

    /**
     * Retrieve true if blog auto related posts are enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAutoRelatedPostsEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_AUTO_RELATED_POSTS_ENABLED,
            $storeId
        ) && $this->isRelatedPostsEnabled($storeId);
    }

    /**
     * Retrieve true if blog auto related products are enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAutoRelatedProductsEnabled($storeId = null): bool
    {
        return $this->getConfig(
            self::XML_AUTO_RELATED_PRODUCTS_ENABLED,
            $storeId
        ) && $this->isRelatedProductsEnabled($storeId);
    }

    /**
     * Retrieve black words for auto related posts
     *
     * @param int|string|null $storeId
     * @return mixed
     */
    public function getAutoRelatedPostsBlackWords($storeId = null)
    {
        return $this->getAutoRelatedBlackWords(
            self::XML_AUTO_RELATED_POSTS_BLACK_WORDS,
            $storeId
        );
    }

    /**
     * Retrieve black words for auto related products
     *
     * @param int|string|null $storeId
     * @return array|null
     */
    public function getAutoRelatedProductsBlackWords($storeId = null)
    {
        return $this->getAutoRelatedBlackWords(
            self::XML_AUTO_RELATED_PRODUCTS_BLACK_WORDS,
            $storeId
        );
    }

    /**
     * Retrieve black words from config
     *
     * @param string $configPath
     * @param int|string|null $storeId
     * @return mixed
     */
    protected function getAutoRelatedBlackWords($configPath, $storeId)
    {
        if (!isset($this->autoRelatedBlackWords[$configPath])) {
            $blackWords = $this->getConfig($configPath, $storeId) ?: '';
            $blackWords = explode(',', $blackWords);
            foreach ($blackWords as $key => $value) {
                $value = trim($value);
                if ($value) {
                    $blackWords[$key] = $value;
                } else {
                    unset($blackWords[$key]);
                }
            }
            $this->autoRelatedBlackWords[$configPath] = $blackWords;
        }
        return $this->autoRelatedBlackWords[$configPath];
    }

    /**
     * Retrieves the encryptor instance.
     *
     * @return \Magento\Framework\Encryption\EncryptorInterface
     */
    protected function getEncryptor()
    {
        if (null === $this->encryptor) {
            $this->encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
        }
        return $this->encryptor;
    }
}

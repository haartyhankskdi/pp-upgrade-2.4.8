<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Magento\Catalog\Model\ResourceModel;

use Magefan\BlogPlus\Model\ResourceModel\ProductRelatedPost;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class ProductPlugin
{
    /**
     * @var ProductRelatedPost
     */
    private $productRelatedPost;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProductPlugin constructor.
     *
     * @param ProductRelatedPost $productRelatedPost
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRelatedPost $productRelatedPost,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->productRelatedPost = $productRelatedPost;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Save related post & product links after product save
     *
     * @param Product $subject
     * @param mixed $result
     * @param mixed $object
     * @return mixed
     */
    public function afterSave(Product $subject, $result, $object)
    {
        if ($object->getId()) {
            $data = $this->request->getParams();
            $links = isset($data['links']) ? $data['links'] : ['blog_related' => []];

            if (is_array($links)) {
                $linkType = 'blog_related';
                if (isset($links[$linkType]) && is_array($links[$linkType])) {
                    $linksData = [];
                    $keys = [
                        'position',
                        'display_on_product',
                        'display_on_post',
                        'auto_related'
                    ];

                    foreach ($links[$linkType] as $item) {
                        $linksData[$item['id']] = [];
                        foreach ($keys as $key) {
                            $linksData[$item['id']][$key] = isset($item[$key]) ? $item[$key] : 0;
                        }
                    }
                    $links[$linkType] = $linksData;
                } else {
                    $links[$linkType] = [];
                }

                /** Save related post & product links */
                try {
                    $linksData = $links[$linkType];

                    $oldIds = $this->productRelatedPost->lookupRelatedPostIds($object->getId());
                    $this->productRelatedPost->updateLinks(
                        $object,
                        array_keys($linksData),
                        $oldIds,
                        'magefan_blog_post_relatedproduct',
                        'post_id',
                        $linksData
                    );
                } catch (\Throwable $e) {
                    $this->logger->error(
                        'Error saving related blog posts in ProductPlugin: ' . $e->getMessage(),
                        ['exception' => $e]
                    );
                }
            }
        }

        return $result;
    }
}

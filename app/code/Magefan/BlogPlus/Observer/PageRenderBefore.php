<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\Blog\Model\Config;

class PageRenderBefore implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * PageRenderBefore constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        $action = $observer->getData('action');
        $resultPage = $observer->getData('result_page');
        $fullActionName = $action->getRequest()->getFullActionName();
        $key = '';

        switch ($fullActionName) {
            case 'blog_index_index':
                $key = 'index';
                break;
            case 'blog_post_view':
                $key = 'post';
                break;
            case 'blog_category_view':
                $key = 'category';
                break;
            case 'blog_tag_view':
                $key = 'tag';
                break;
            case 'blog_author_view':
                $key = 'author';
                break;
            case 'blog_search_index':
                $key = 'search';
                break;
        }

        $layout = $this->config->getConfig('mfblog/design/' . $key . '_page_layout');

        if ($layout) {
            $resultPage->getConfig()->setPageLayout($layout);
        }
    }
}

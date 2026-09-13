<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogImport\Controller\Adminhtml\Post;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

class ExportCsv extends \Magefan\Blog\Controller\Adminhtml\Post
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * Export rates grid to CSV format
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        $filter = $this->getRequest()->getParam('filter');
        if ($filter) {
			// phpcs:disable Magento2.Functions.DiscouragedFunction
            $filter = base64_decode($filter);
            parse_str($filter, $filter);
            foreach ($filter as $k => $v) {
                if (is_array($v)) {
                    unset($filter[$k]);
                }
            }
            $filter = base64_encode(http_build_query($filter));
			// phpcs:enable Magento2.Functions.DiscouragedFunction
            $this->getRequest()->setParam('filter', $filter);
        }
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $content = $resultLayout->getLayout()->getChildBlock('blog.post.grid', 'grid.export');

        return $this->getFileFactory()->create(
            'blog_posts.csv',
            $content->getCsvFile(),
            DirectoryList::VAR_DIR
        );
    }

    /**
     * Get file factory
     *
     * @return \Magento\Framework\App\Response\Http\FileFactory
     */
    private function getFileFactory()
    {
        if (null === $this->fileFactory) {
            $this->fileFactory = $this->_objectManager->get(\Magento\Framework\App\Response\Http\FileFactory::class);
        }
        return $this->fileFactory;
    }
}

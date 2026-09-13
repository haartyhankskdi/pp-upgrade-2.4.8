<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\BlogPlus\Block\Category;

class BottomContent extends \Magefan\Blog\Block\Category\AbstractCategory
{
    /**
     * Get bottom content
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getBottomContent()
    {
        $category = $this->getCategory();
        $key = 'filtered_bottom_content';
        if (!$category->hasData($key)) {
            $content = $this->_filterProvider->getPageFilter()->filter(
                (string) $category->getBottomContent() ?: ''
            );
            $category->setData($key, $content);
        }
        return $category->getData($key);
    }
}

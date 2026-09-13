<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Block\Post\View;

use Magefan\Blog\Block\Post\View\Comments;

class CommentsPlugin
{
    /**
     * Show comments only if enabled
     *
     * @param Comments $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundToHtml(Comments $subject, callable $proceed)
    {
        if (!$subject->getPost()->getEnableComments()) {
            return '';
        }

        return $proceed();
    }
}

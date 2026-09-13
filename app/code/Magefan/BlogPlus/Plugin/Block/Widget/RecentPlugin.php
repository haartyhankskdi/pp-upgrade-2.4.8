<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Block\Widget;

use Magefan\Blog\Block\Widget\Recent;
use Magento\Framework\Exception\LocalizedException;

class RecentPlugin
{

    /**
     * Modifies the HTML output for a recent post block by appending rich snippets if enabled.
     *
     * @param Recent $subject
     * @param string|null $result
     * @return string|null
     * @throws LocalizedException
     */
    public function afterToHtml(Recent $subject, ?string $result): ?string
    {
        if ($result && $subject->getData('include_post_rich_snippet')) {
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
}

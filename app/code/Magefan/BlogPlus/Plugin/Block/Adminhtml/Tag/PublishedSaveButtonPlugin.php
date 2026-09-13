<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Block\Adminhtml\Tag;

use Magefan\BlogPlus\Plugin\Block\Adminhtml\AbstractPublishedSaveButtonPlugin;

class PublishedSaveButtonPlugin extends AbstractPublishedSaveButtonPlugin
{
    /**
     * Remove save button if it's not allowed
     *
     * @param \Magefan\Community\Block\Adminhtml\Edit\GenericButton $subject
     * @param array $result
     * @return array
     */
    public function afterGetButtonData(
        \Magefan\Community\Block\Adminhtml\Edit\GenericButton $subject,
        $result
    ) {
        if (!$this->authorization->isAllowed('Magefan_BlogPlus::tag_save_published')) {
            $model = $this->registry->registry('current_model');
            if ($model->isActive()) {
                return [];
            }
        }
        return $result;
    }
}

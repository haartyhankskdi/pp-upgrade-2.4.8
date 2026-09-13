<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

class TagPublishPermissionPlugin extends PublishPermissionPlugin
{
    /**
     * Check if action is allowed
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param mixed $object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave(
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource,
        $object
    ): void {

        $this->processor->execute(
            $resource,
            $object,
            'Magefan_BlogPlus::tag_save_published',
            'is_active'
        );
    }
}

<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Model\ResourceModel;

/**
 * Class PublishPermissionPlugin
 */
abstract class PublishPermissionPlugin
{
    /**
     * @var \Magefan\BlogPlus\Plugin\Model\ResourceModel\PublishPermissionPlugin\Processor
     */
    protected $processor;

    /**
     * PublishPermissionPlugin constructor.
     * @param PublishPermissionPlugin\Processor $processor
     */
    public function __construct(
        PublishPermissionPlugin\Processor $processor
    ) {
        $this->processor = $processor;
    }
}

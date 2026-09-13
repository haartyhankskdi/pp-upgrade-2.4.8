<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Cron;

class AutoRelated
{
    /**
     * @var \Magefan\BlogPlus\Model\AutoRelated\PostProcessor
     */
    protected $postProcessor;

    /**
     * @var \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor
     */
    protected $productProcessor;

    /**
     * AutoRelated constructor.
     * @param \Magefan\BlogPlus\Model\AutoRelated\PostProcessor $postProcessor
     * @param \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor $productProcessor
     */
    public function __construct(
        \Magefan\BlogPlus\Model\AutoRelated\PostProcessor $postProcessor,
        \Magefan\BlogPlus\Model\AutoRelated\ProductProcessor $productProcessor
    ) {
        $this->postProcessor = $postProcessor;
        $this->productProcessor = $productProcessor;
    }

    /**
     * Method which called in cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->postProcessor->execute();
        $this->productProcessor->execute();
    }
}

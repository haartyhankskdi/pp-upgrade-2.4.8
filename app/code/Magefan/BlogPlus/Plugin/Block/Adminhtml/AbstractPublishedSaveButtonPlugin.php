<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Plugin\Block\Adminhtml;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;

/**
 * Class AbstractPublishedSaveButtonPlugin
 * @package Magefan\BlogPlus\Plugin\Block\Adminhtml
 */
abstract class AbstractPublishedSaveButtonPlugin
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * AbstractPublishedSaveButtonPlugin constructor.
     * @param AuthorizationInterface $authorization
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        AuthorizationInterface $authorization,
        Registry $registry
    ) {
        $this->authorization = $authorization;
        $this->registry = $registry;
    }
}

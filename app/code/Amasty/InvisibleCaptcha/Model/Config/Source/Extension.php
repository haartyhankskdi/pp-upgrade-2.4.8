<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Invisible reCaptcha for Magento 2
 */

namespace Amasty\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Module\Manager;

class Extension implements OptionSourceInterface
{
    public const EXTENSION_NOT_INSTALLED = -1;
    public const INTEGRATION_DISABLED = 0;
    public const INTEGRATION_ENABLED = 1;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var string
     */
    private $extension = '';

    public function __construct(
        Manager $moduleManager,
        $moduleName = ''
    ) {
        $this->moduleManager = $moduleManager;
        $this->extension = $moduleName;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->moduleManager->isEnabled($this->extension)) {
            return [
                ['value' => self::INTEGRATION_DISABLED, 'label' => __('No')],
                ['value' => self::INTEGRATION_ENABLED, 'label' => __('Yes')],
            ];
        }

        return [['value' => self::EXTENSION_NOT_INSTALLED, 'label' => __('Not Installed')]];
    }
}

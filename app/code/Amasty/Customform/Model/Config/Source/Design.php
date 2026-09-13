<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Design implements ArrayInterface
{
    public const DEFAULT_THEME = 0;
    public const LINEAR_THEME = 1;
    public const CIRCLE_THEME = 2;

    public const DEFAULT_THEME_CLASS = 'default';
    public const LINEAR_THEME_CLASS = 'linear-theme';
    public const CIRCLE_THEME_CLASS = 'circle-theme';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::DEFAULT_THEME, 'label' => __('Default')],
            ['value' => self::LINEAR_THEME, 'label' => __('Linear Theme')],
            ['value' => self::CIRCLE_THEME, 'label' => __('Circle Theme')],
        ];
    }
}

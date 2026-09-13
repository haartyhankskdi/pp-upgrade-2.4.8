<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Invisible reCaptcha for Magento 2
 */

namespace Amasty\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BadgePosition implements OptionSourceInterface
{
    public const BADGE_POSITION_BOTTOMRIGHT = 'bottomright';
    public const BADGE_POSITION_BOTTOMLEFT = 'bottomleft';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::BADGE_POSITION_BOTTOMRIGHT, 'label'=> __('Bottom Right')],
            ['value' => self::BADGE_POSITION_BOTTOMLEFT, 'label'=> __('Bottom Left')]
        ];
    }
}

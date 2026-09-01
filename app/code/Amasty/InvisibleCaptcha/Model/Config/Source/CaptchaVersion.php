<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Google Invisible reCaptcha for Magento 2
*/

declare(strict_types=1);

namespace Amasty\InvisibleCaptcha\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CaptchaVersion implements OptionSourceInterface
{
    public const VERSION_2 = 2;
    public const VERSION_3 = 3;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::VERSION_2, 'label'=> __('Version 2')],
            ['value' => self::VERSION_3, 'label'=> __('Version 3')]
        ];
    }
}

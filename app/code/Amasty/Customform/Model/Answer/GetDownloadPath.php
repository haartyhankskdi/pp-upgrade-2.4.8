<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

class GetDownloadPath
{
    public const AMASTY_CUSTOMFORM_VAR_PATH = 'amasty/amcustomform';

    /**
     * @param string $fileName
     * @return string
     */
    public function execute(string $fileName): string
    {
        return sprintf('%s/%s', self::AMASTY_CUSTOMFORM_VAR_PATH, $fileName);
    }
}

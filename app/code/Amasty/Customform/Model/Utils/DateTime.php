<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Utils;

use Amasty\Customform\Model\ConfigProvider;
use Magento\Framework\Stdlib\DateTime\DateTime as MagentoDateTime;

class DateTime
{
    /**
     * @var MagentoDateTime
     */
    private $dateTime;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider,
        MagentoDateTime $dateTime
    ) {
        $this->dateTime = $dateTime;
        $this->configProvider = $configProvider;
    }

    /**
     * @return int
     */
    public function getCurrentDateTime(): int
    {
        return $this->dateTime->timestamp();
    }

    /**
     * @param int $dateTime
     * @return bool
     */
    public function isValidLink(int $dateTime): bool
    {
        $lifetime = $this->configProvider->getFileLinkLifetime();
        if ($lifetime && $dateTime) {
            $start = $this->dateTime->timestamp(sprintf('-%s days', $lifetime));
            $end = $this->getCurrentDateTime();
            $result = $dateTime >= $start && $dateTime <= $end;
        }

        return $result ?? true;
    }
}

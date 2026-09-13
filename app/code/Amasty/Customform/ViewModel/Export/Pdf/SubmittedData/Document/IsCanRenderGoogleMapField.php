<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData\Document;

use Amasty\Customform\Helper\Data as ConfigProvider;
use Amasty\Customform\Model\Submit;

class IsCanRenderGoogleMapField implements IsCanRenderFieldInterface
{
    public const GOOGLE_MAP_FIELD_TYPE = 'googlemap';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    public function isCanRender(array $fieldConfig): bool
    {
        $fieldType = $fieldConfig[Submit::TYPE] ?? '';
        $result = true;

        if ($fieldType === self::GOOGLE_MAP_FIELD_TYPE) {
            $result = $this->configProvider->isCanRenderGoogleMapInPdf();
        }

        return $result;
    }
}

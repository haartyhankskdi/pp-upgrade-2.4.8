<?php

declare(strict_types=1);

namespace Amasty\Reports\Block\Adminhtml;

use Amasty\Base\Model\ModuleInfoProvider;
use Magento\Backend\Block\Template;

class Tooltip extends Template
{
    /**
     * @var ModuleInfoProvider
     */
    private $moduleInfoProvider;

    public function __construct(
        Template\Context $context,
        ModuleInfoProvider $moduleInfoProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleInfoProvider = $moduleInfoProvider;
    }

    /**
     * @var array
     */
    protected $config;

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getLinkUrl(array $element): string
    {
        return $this->moduleInfoProvider->isOriginMarketplace()
            ? ($element['adv_m_url'] ?? '')
            : ($element['adv_url'] ?? '');
    }
}

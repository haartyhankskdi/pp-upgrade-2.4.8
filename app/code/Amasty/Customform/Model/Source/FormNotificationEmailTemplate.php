<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FormNotificationEmailTemplate implements OptionSourceInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Email\Template
     */
    private $emailTemplateSource;

    public function __construct(
        \Magento\Config\Model\Config\Source\Email\Template $emailTemplateSource
    ) {
        $this->emailTemplateSource = $emailTemplateSource;
    }

    public function toOptionArray()
    {
        return $this->emailTemplateSource->setPath('amasty/customform/email/template')->toOptionArray();
    }
}

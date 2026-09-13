<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Amasty Custom Forms GraphQl for Magento 2 (System)
 */

namespace Amasty\CustomformGraphQl\Plugin\Customform\Model;

use Amasty\Customform\Model\Form;
use Amasty\Customform\Model\Submit;

class SubmitPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    public function aroundIsValidFormKey(): bool
    {
        return true;
    }

    public function aroundAddRefererUrlIfNeed(Submit $subject, callable $proceed): string
    {
        return $this->request->getServer('HTTP_REFERER');
    }
}

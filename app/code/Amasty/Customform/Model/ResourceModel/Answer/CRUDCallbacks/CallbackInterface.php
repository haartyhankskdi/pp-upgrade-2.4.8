<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\ResourceModel\Answer\CRUDCallbacks;

use Amasty\Customform\Api\Data\AnswerInterface;

interface CallbackInterface
{
    public function process(AnswerInterface $answer): void;
}

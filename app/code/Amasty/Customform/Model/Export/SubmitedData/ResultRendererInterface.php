<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Export\SubmitedData;

use Amasty\Customform\Api\Data\AnswerInterface;

interface ResultRendererInterface
{
    public function render(AnswerInterface $answer): string;
}

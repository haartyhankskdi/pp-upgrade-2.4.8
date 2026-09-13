<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering\FormJson;

use Amasty\Customform\Api\Data\FormInterface;

interface ModifierInterface
{
    public function execute(FormInterface $form, array $formJson): array;
}

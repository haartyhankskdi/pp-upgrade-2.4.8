<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\Rendering;

use Amasty\Base\Model\Serializer;
use Amasty\Customform\Api\Data\FormInterface;
use Amasty\Customform\Model\Form\Rendering\FormJson\ModifierInterface;

class GetFormJson
{
    /**
     * @var ModifierInterface[]
     */
    private $formJsonModifiers;
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(Serializer $serializer, array $formJsonModifiers = [])
    {
        $this->serializer = $serializer;
        $this->formJsonModifiers = $formJsonModifiers;
    }

    public function execute(FormInterface $form): string
    {
        $formJson = $form->getFormJson();
        $formConfig = $this->serializer->unserialize($formJson);

        foreach ($this->formJsonModifiers as $formJsonModifier) {
            $formConfig = $formJsonModifier->execute($form, $formConfig);
        }

        return $this->serializer->serialize($formConfig);
    }
}

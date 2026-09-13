<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Config;

use Amasty\Customform\Model\ResourceModel\Form\CollectionFactory as FormCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class GdprCustomform implements OptionSourceInterface
{
    public const CUSTOM_FORM = '_custom_form';

    /**
     * @var FormCollectionFactory
     */
    private $formCollectionFactory;

    public function __construct(
        FormCollectionFactory $formCollectionFactory
    ) {
        $this->formCollectionFactory = $formCollectionFactory;
    }

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        $forms = [];

        foreach ($this->formCollectionFactory->create() as $form) {
            $forms[] = [
                'value' => $form->getId() . self::CUSTOM_FORM,
                'label' => $form->getTitle()
            ];
        }

        return [
            [
                'value' => $forms,
                'label' => __('Custom Form')
            ]
        ];
    }
}

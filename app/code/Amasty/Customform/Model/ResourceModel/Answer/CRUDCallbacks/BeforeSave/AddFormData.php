<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\ResourceModel\Answer\CRUDCallbacks\BeforeSave;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\CachingFormProvider;
use Amasty\Customform\Model\ResourceModel\Answer\CRUDCallbacks\CallbackInterface;

class AddFormData implements CallbackInterface
{
    /**
     * @var CachingFormProvider
     */
    private $cachingFormProvider;

    public function __construct(
        CachingFormProvider $cachingFormProvider
    ) {
        $this->cachingFormProvider = $cachingFormProvider;
    }

    public function process(AnswerInterface $answer): void
    {
        if ($answer->getFormName() === null) {
            $form = $this->cachingFormProvider->getById($answer->getFormId());

            if ($form) {
                $answer->setFormName($form->getTitle());
                $answer->setFormCode($form->getCode());
            }
        }
    }
}

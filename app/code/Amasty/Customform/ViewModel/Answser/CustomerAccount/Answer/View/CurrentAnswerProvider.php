<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\ViewModel\Answser\CustomerAccount\Answer\View;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Api\Data\AnswerInterfaceFactory;
use Amasty\Customform\Model\FormRegistry;

class CurrentAnswerProvider
{
    /**
     * @var FormRegistry
     */
    private $formRegistry;

    /**
     * @var AnswerInterfaceFactory
     */
    private $answerInterfaceFactory;

    public function __construct(
        FormRegistry $formRegistry,
        AnswerInterfaceFactory $answerInterfaceFactory
    ) {
        $this->formRegistry = $formRegistry;
        $this->answerInterfaceFactory = $answerInterfaceFactory;
    }

    public function getCurrentResponse(): AnswerInterface
    {
        $answer = $this->formRegistry->getCurrentAnswer();

        if ($answer === null) {
            $answer = $this->answerInterfaceFactory->create();
        }

        return $answer;
    }
}

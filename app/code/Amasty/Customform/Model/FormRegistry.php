<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Api\Data\FormInterface;

class FormRegistry
{
    public const CURRENT_FORM = 'amasty_current_form';
    public const PERSISTED_DATA = 'persisted_data';
    public const CURRENT_ANSWER = 'current_answer';

    /**
     * @var object[]
     */
    private $dataCollection = [];

    public function registry($key)
    {
        return $this->dataCollection[$key] ?? null;
    }

    public function register($key, $value, $graceful = false): void
    {
        if (isset($this->dataCollection[$key])) {
            if ($graceful) {
                return;
            }

            throw new \RuntimeException(__('Registry key "%1" already exists', $key)->render());
        }

        $this->dataCollection[$key] = $value;
    }

    public function getCurrentForm(): ?FormInterface
    {
        $form = $this->registry(self::CURRENT_FORM);

        return $form instanceof FormInterface ? $form : null;
    }

    public function setCurrentForm(FormInterface $form): void
    {
        $this->register(self::CURRENT_FORM, $form, true);
    }

    public function setCurrentAnswer(AnswerInterface $answer): void
    {
        $this->register(self::CURRENT_ANSWER, $answer, true);
    }

    public function getCurrentAnswer(): ?AnswerInterface
    {
        $answer = $this->registry(self::CURRENT_ANSWER);

        return $answer instanceof AnswerInterface ? $answer : null;
    }

    public function _resetState(): void
    {
        $this->dataCollection = [];
    }
}

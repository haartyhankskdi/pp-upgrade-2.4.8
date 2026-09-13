<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Observer\Answer;

use Amasty\Customform\Api\Data\AnswerInterface;
use Amasty\Customform\Model\Answer\FileRemover;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteAfter implements ObserverInterface
{
    /**
     * @var FileRemover
     */
    private $fileRemover;

    public function __construct(
        FileRemover $fileRemover
    ) {
        $this->fileRemover = $fileRemover;
    }

    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $answer = $event->getData('data_object');

        if ($answer instanceof AnswerInterface) {
            $this->fileRemover->execute($answer);
        }
    }
}

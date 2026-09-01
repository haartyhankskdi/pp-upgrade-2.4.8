<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Email;

use Amasty\Base\Model\MagentoVersion;
use Amasty\Reports\Model\Di\WrapperFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Message\MessageInterface;

class MessageBuilder
{
    /**
     * @var EmailMessageInterfaceFactory
     */
    private $emailMessageFactory;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private $mimeMessageFactory;

    /**
     * @var EmailMessageInterface|MessageInterface
     */
    private $oldMessage;

    /**
     * @var array
     */
    private $messageParts = [];

    /**
     * @var bool
     */
    private $isNewVersion;

    public function __construct(
        MagentoVersion $magentoVersion,
        WrapperFactory $emailMessageFactory,
        WrapperFactory $mimeMessageFactory
    ) {
        $this->isNewVersion = version_compare($magentoVersion->get(), '2.3.3', '>=');
        $this->emailMessageFactory = $emailMessageFactory;
        $this->mimeMessageFactory = $mimeMessageFactory;
    }

    /**
     * @return EmailMessageInterface|MessageInterface
     * @throws LocalizedException
     */
    public function build()
    {
        if ($this->isNewVersion) {
            return $this->buildUsingEmailMessageInterfaceFactory();
        }

        return $this->replaceMessageBody();
    }

    /**
     * @return EmailMessageInterface
     * @throws LocalizedException
     */
    private function buildUsingEmailMessageInterfaceFactory()
    {
        $this->checkDependencies();
        $parts = $this->oldMessage->getBody()->getParts();
        $parts = array_merge($parts, $this->messageParts);
        $messageData = [
            'body' => $this->mimeMessageFactory->create(
                ['parts' => $parts]
            ),
            'from' => $this->oldMessage->getFrom(),
            'to' => $this->oldMessage->getTo(),
            'subject' => $this->oldMessage->getSubject()
        ];

        return $this->emailMessageFactory->create($messageData);
    }

    /**
     * @return MessageInterface
     * @throws LocalizedException
     */
    private function replaceMessageBody()
    {
        $this->checkDependencies();

        if (!empty($this->messageParts)) {
            /** @var \Zend\Mime\Part $part */
            foreach ($this->messageParts as $part) {
                $this->oldMessage->getBody()->addPart($part);
            }

            $this->oldMessage->setBody($this->oldMessage->getBody());
        }

        return $this->oldMessage;
    }

    /**
     * @throws LocalizedException
     */
    private function checkDependencies(): void
    {
        if ($this->oldMessage === null) {
            throw new LocalizedException(__('To create a message, you need it\'s prototype...'));
        }
    }

    public function setOldMessage($oldMessage)
    {
        $this->oldMessage = $oldMessage;
    }

    public function setMessageParts(array $messageParts)
    {
        $this->messageParts = $messageParts;
    }
}

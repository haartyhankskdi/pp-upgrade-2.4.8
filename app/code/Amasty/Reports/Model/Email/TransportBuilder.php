<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Email;

use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var MessageBuilderFactory
     */
    private $messageBuilderFactory;

    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageBuilderFactory $messageBuilderFactory
    ) {
        $this->messageBuilderFactory = $messageBuilderFactory;

        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
    }

    /**
     * @param string $body
     * @param string $fileName
     * @param string $mimeType
     * @param string $disposition
     * @param string $encoding
     * @return $this
     */
    public function addAttachment(
        $body,
        $fileName,
        $mimeType = MimeInterface::TYPE_OCTET_STREAM,
        $disposition = MimeInterface::DISPOSITION_ATTACHMENT,
        $encoding = MimeInterface::ENCODING_BASE64
    ) {
        /** @var MimePartInterfaceFactory $mimePartInterfaceFactory */
        $mimePartInterfaceFactory = $this->objectManager->get(MimePartInterfaceFactory::class);
        $attachment = $mimePartInterfaceFactory->create(
            [
                'content' => $body,
                'type' => $mimeType,
                'fileName' => $fileName,
                'disposition' => $disposition,
                'encoding' => $encoding
            ]
        );
        $this->parts[] = $attachment;

        return $this;
    }

    /**
     * @return $this|TransportBuilder
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();

        /**
         * @var MessageBuilder $messageBuilder
         */
        $messageBuilder = $this->messageBuilderFactory->create();
        $messageBuilder->setOldMessage($this->message);
        $messageBuilder->setMessageParts($this->parts);
        $this->message = $messageBuilder->build();

        return $this;
    }

    protected function reset()
    {
        $this->parts = [];
        return parent::reset();
    }
}

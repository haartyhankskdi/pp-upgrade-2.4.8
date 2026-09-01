<?php

declare(strict_types=1);

namespace Amasty\Reports\Model\Email;

use Magento\Framework\Mail\MessageInterface;
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
        $mimeType = \Zend_Mime::TYPE_OCTETSTREAM,
        $disposition = \Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Zend_Mime::ENCODING_BASE64
    ) {
        $attachment = new \Zend\Mime\Part($body);
        $attachment->encoding = $encoding;
        $attachment->type = $mimeType;
        $attachment->disposition = $disposition;
        $attachment->filename = $fileName;
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

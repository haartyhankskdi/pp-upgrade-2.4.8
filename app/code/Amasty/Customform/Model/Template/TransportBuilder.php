<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

namespace Amasty\Customform\Model\Template;

use Amasty\Customform\Model\Mail\MessageBuilder;
use Amasty\Customform\Model\Mail\MessageBuilderFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder as Transport;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class TransportBuilder extends Transport
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var MessageBuilderFactory
     */
    protected $messageBuilderFactory;

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
     * @param $body
     * @param null $filename
     * @param string $mimeType
     * @param string $disposition
     * @param string $encoding
     *
     * @return $this
     */
    public function addAttachment(
        $body,
        $filename = null,
        $mimeType = MimeInterface::TYPE_OCTET_STREAM,
        $disposition = MimeInterface::DISPOSITION_ATTACHMENT,
        $encoding = MimeInterface::ENCODING_BASE64
    ) {
        if ($this->message && method_exists($this->message, 'createAttachment')) {
            $this->message->createAttachment(
                $body,
                $mimeType,
                $disposition,
                $encoding,
                $filename
            );
        } else {
            /** @var MimePartInterfaceFactory $mimePartInterfaceFactory */
            $mimePartInterfaceFactory = $this->objectManager->get(MimePartInterfaceFactory::class);
            $mp = $mimePartInterfaceFactory->create(
                [
                    'content' => $body,
                    'type' => $mimeType,
                    'fileName' => $filename,
                    'disposition' => $disposition,
                    'encoding' => $encoding
                ]
            );
            $this->parts[] = $mp;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();

        /**
         * @var MessageBuilder $messageBuilder
         */
        $messageBuilder = $this->messageBuilderFactory->create();
        $this->message = $messageBuilder
            ->setOldMessage($this->message)
            ->setMessageParts($this->parts)
            ->build();

        return $this;
    }
}

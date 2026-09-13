<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Model\Mail\Template;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Mirasvit\Core\Service\CompatibilityService;
use Magento\Framework\Mail\MimePart;
use Magento\Framework\Module\Dir\Reader;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Part\TextPart;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder implements TransportBuilderInterface
{
    protected $moduleReader;

    /**
     * @var \Magento\Framework\Mail\EmailMessageInterfaceFactory|null
     */
    private $emailMessageInterfaceFactory;

    /**
     * @var \Magento\Framework\Mail\MimeMessageInterfaceFactory|null
     */
    private $mimeMessageInterfaceFactory;

    /**
     * @var array
     */
    protected $attachments = [];

    /**
     * @var array
     */
    protected $mstCustomHeaders = [];

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * TransportBuilder constructor.
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param ProductMetadataInterface $productMetadata
     * @param Manager $moduleManager
     * @param MessageInterfaceFactory|null $messageFactory
     * @param \Magento\Framework\Mail\EmailMessageInterfaceFactory|null $emailMessageInterfaceFactory
     * @param \Magento\Framework\Mail\MimeMessageInterfaceFactory|null $mimeMessageInterfaceFactory
     * @param \Magento\Framework\Mail\MimePartInterfaceFactory|null $mimePartInterfaceFactory
     * @param \Magento\Framework\Mail\AddressConverter|null $addressConverter
     */
    public function __construct(
        Reader                    $moduleReader,
        FactoryInterface          $templateFactory,
        MessageInterface          $message,
        SenderResolverInterface   $senderResolver,
        ObjectManagerInterface    $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        ProductMetadataInterface  $productMetadata,
        Manager                   $moduleManager,
        ?MessageInterfaceFactory  $messageFactory = null,
                                  $emailMessageInterfaceFactory = null,
                                  $mimeMessageInterfaceFactory = null,
                                  $mimePartInterfaceFactory = null,
                                  $addressConverter = null
    ) {
        $this->moduleReader    = $moduleReader;
        $this->productMetadata = $productMetadata;
        $this->moduleManager   = $moduleManager;

        if ($this->isBelow233()) {
            parent::__construct($templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory);
        } else {
            parent::__construct(
                $templateFactory, $message, $senderResolver, $objectManager, $mailTransportFactory,
                $messageFactory, $emailMessageInterfaceFactory, $mimeMessageInterfaceFactory, $mimePartInterfaceFactory,
                $addressConverter
            );
            $this->emailMessageInterfaceFactory = $emailMessageInterfaceFactory ?: $this->objectManager
                ->get(\Magento\Framework\Mail\EmailMessageInterfaceFactory::class);
            $this->mimeMessageInterfaceFactory  = $mimeMessageInterfaceFactory ?: $this->objectManager
                ->get(\Magento\Framework\Mail\MimeMessageInterfaceFactory::class);
        }

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        parent::reset();
        $this->attachments      = [];
        $this->mstCustomHeaders = [];

        return $this;
    }

    /**
     * The concrete message class depends on the Magento version: Zend_Mail-based (<2.3.3),
     * laminas EmailMessage (2.3.3–2.4.7), symfony-backed EmailMessage (2.4.8+) — so callers
     * in the version-guarded branches use methods no single platform can type-check.
     * @return mixed
     */
    private function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttachment(
        $body,
        $mimeType = \Magento\Framework\HTTP\Mime::TYPE_OCTETSTREAM,
        $disposition = \Magento\Framework\HTTP\Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Magento\Framework\HTTP\Mime::ENCODING_BASE64,
        $filename = null
    ) {
        if ($body instanceof \Fooman\EmailAttachments\Model\Api\AttachmentInterface &&
            $this->moduleManager->isEnabled('Fooman_EmailAttachments')
        ) {
            $mimeType    = $body->getMimeType();
            $disposition = $body->getDisposition();
            $encoding    = $body->getEncoding();
            $filename    = $body->getFilename();
            $body        = $body->getContent();
        }
        if ($this->is248() || $this->is249()) {
            /** We do not know the file extension here */
            $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
            $types  = require($this->moduleReader->getModuleDir('etc', 'Mirasvit_Helpdesk') . '/mime_types.php');
            $mimeType = $types[$extension] ?? 'application/octet-stream';
            if (is_array($mimeType)) {
                $first = reset($mimeType);
                $mimeType = !empty($first) ? $first : 'application/octet-stream';
            }
            /**-------*/
            $attach = new MimePart($body, $mimeType, $filename, $disposition, $encoding);
            $this->attachments[] = $attach->getMimePart();

            return $this;
        } else {
            if (version_compare(CompatibilityService::getVersion(), "2.4.3-p3", ">=") && !$this->is248()) {
                $attach = new \Laminas\Mime\Part($body);
            } else {
                $attach = new \Zend\Mime\Part($body);
            }
            $attach->setType($mimeType);
            $attach->setEncoding($encoding);
            $attach->setFileName($filename);
        }

        $attach->setDisposition($disposition);
        $this->attachments[] = $attach;

        return $this;
    }

    /**
     * @return \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();

        $laminasExists = (version_compare(CompatibilityService::getVersion(), "2.3.5", ">"));

        if (!count($this->mstCustomHeaders) && (!count($this->attachments))) {
            return $this;
        }

        if ($this->getMessage() instanceof \Ebizmarts\Mandrill\Model\Message) {
            /** @var \Zend\Mime\Part $attachment */
            foreach ($this->attachments as $attachment) {
                $this->getMessage()->createAttachment(
                    base64_decode($attachment->getContent()),
                    $attachment->getType(),
                    $attachment->getDescription(),
                    $attachment->getEncoding(),
                    $attachment->getFileName()
                );
            }
        } elseif ($this->is249()) {
            /** @var \Magento\Framework\Mail\EmailMessage $originalMessage */
            $originalMessage = $this->message;
            $symfonyMessage = $originalMessage->getSymfonyMessage();

            $htmlBody = $symfonyMessage->getBody();
            if ($htmlBody instanceof MixedPart) {
                foreach ($htmlBody->getParts() as $part) {
                    if ($part instanceof \Symfony\Component\Mime\Part\TextPart) {
                        $htmlBody = $part->getBody();
                        break;
                    }
                }
            } elseif ($htmlBody instanceof \Symfony\Component\Mime\Part\TextPart) {
                $htmlBody = $htmlBody->getBody();
            } elseif ($htmlBody === null) {
                $htmlBody = '';
            }
            if (!is_string($htmlBody)) {
                // MixedPart without a TextPart child: serialize instead of fataling in TextPart's ctor.
                $htmlBody = $htmlBody->toString();
            }

            $symfonyMessage = $originalMessage->getSymfonyMessage();
            $bodyPart = new TextPart($htmlBody, 'utf-8', 'html');
            $mixedPart = new MixedPart($bodyPart, ...$this->attachments);
            $symfonyMessage->setBody($mixedPart);
        } elseif ($this->isBelow233()) {
            $parts = $this->getMessage()->getBody()->getParts();
            $parts = array_merge($parts, $this->attachments);
            $body  = new \Zend\Mime\Message();
            $body->setParts($parts);
            $this->getMessage()->setBody($body);
        } else {
            if ($this->is248()) {
                /** @var \Magento\Framework\Mail\EmailMessage $originalMessage */
                $originalMessage = $this->message;
                $symfonyMessage = $originalMessage->getSymfonyMessage();

                $htmlBody = $symfonyMessage->getBody();
                if ($htmlBody instanceof MixedPart) {

                    foreach ($htmlBody->getParts() as $part) {
                        if ($part instanceof \Symfony\Component\Mime\Part\TextPart) {
                            $htmlBody = $part->getBody();
                            break;
                        }
                    }
                } elseif ($htmlBody instanceof \Symfony\Component\Mime\Part\TextPart) {
                    $htmlBody = $htmlBody->getBody();
                } elseif ($htmlBody === null) {
                    $htmlBody = '';
                }
                if (!is_string($htmlBody)) {
                    // MixedPart without a TextPart child: serialize instead of fataling in TextPart's ctor.
                    $htmlBody = $htmlBody->toString();
                }

                $symfonyMessage = $originalMessage->getSymfonyMessage();
                $bodyPart = new TextPart($htmlBody, 'utf-8', 'html');
                $mixedPart = new MixedPart($bodyPart, ...$this->attachments);
                $symfonyMessage->setBody($mixedPart);
            } else {
                $parts = $this->getMessage()->getBody()->getParts();
                $parts = array_merge($parts, $this->attachments);

                $messageData         = [
                    'encoding' => $this->getMessage()->getEncoding(),
                    'subject'  => $this->getMessage()->getSubject(),
                    'sender'   => $this->getMessage()->getSender(),
                    'to'       => $this->getMessage()->getTo(),
                    'replyTo'  => $this->getMessage()->getReplyTo(),
                    'from'     => $this->getMessage()->getFrom(),
                    'cc'       => $this->getMessage()->getCc(),
                    'bcc'      => $this->getMessage()->getBcc(),
                ];
                /** @var \Magento\Framework\Mail\MimeMessageInterfaceFactory $mimeMessageFactory */
                $mimeMessageFactory  = $this->mimeMessageInterfaceFactory;
                $messageData['body'] = $mimeMessageFactory->create(
                    ['parts' => $parts]
                );

                if ($laminasExists) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    /** @var \Mirasvit\Helpdesk\Model\Mail\HelpdeskCustomMessage $message */
                    $message = $objectManager->create(\Mirasvit\Helpdesk\Model\Mail\HelpdeskCustomMessage::class, $messageData);
                    foreach ($this->mstCustomHeaders as $key => $value) {
                        $message->addHeader($key, $value, true);
                    }
                    $this->message = $message;
                } else {
                    /** @var \Magento\Framework\Mail\EmailMessageInterfaceFactory $emailMessageFactory */
                    $emailMessageFactory = $this->emailMessageInterfaceFactory;
                    $this->message       = $emailMessageFactory->create($messageData);
                }
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function is248()
    {
        return version_compare($this->productMetadata->getVersion(), "2.4.8", ">=");
    }

    /**
     * @return bool
     */
    protected function is249(): bool
    {
        return version_compare($this->productMetadata->getVersion(), "2.4.9", ">=");
    }

    /**
     * @return bool
     */
    protected function isBelow233()
    {
        return version_compare($this->productMetadata->getVersion(), "2.3.3", "<");
    }

    /**
     * {@inheritdoc}
     */
    public function addCustomHeader($mstCustomHeaders)
    {
        $this->mstCustomHeaders = $mstCustomHeaders;

        return $this;
    }
}

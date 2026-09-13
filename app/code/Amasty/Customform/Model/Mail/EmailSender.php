<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Mail;

use Amasty\Base\Utils\Email\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\HTTP\Mime;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    public function __construct(
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * @param string|array $receivers
     * @param string $templateIdentifier
     * @param string $sender
     * @param array $templateVars
     * @param string[] $attachments
     * @param int|null $storeId
     * @param string|null $replyTo
     *
     * @throws LocalizedException
     * @throws MailException
     */
    public function sendMail(
        $receivers,
        string $templateIdentifier,
        string $sender,
        array $templateVars = [],
        array $attachments = [],
        ?int $storeId = null,
        ?string $replyTo = null
    ): void {
        $this->addReceivers($receivers);
        $this->transportBuilder->setTemplateIdentifier($templateIdentifier);
        $this->addTemplateOptions($storeId);
        $this->transportBuilder->setTemplateVars($templateVars);
        $this->transportBuilder->setFromByScope($sender, $storeId);
        if ($replyTo) {
            $this->transportBuilder->setReplyTo($replyTo);
        }
        foreach ($attachments as $fileName => $content) {
            $this->transportBuilder->addAttachment($content, $fileName, Mime::TYPE_OCTETSTREAM);
        }

        $this->transportBuilder->getTransport()->sendMessage();
    }

    private function addTemplateOptions(
        ?int $storeId = null
    ): void {
        if ($storeId === null) {
            $storeId = (int) $this->storeManager->getStore()->getId();
        }

        $this->transportBuilder->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId]);
    }

    /**
     * @param string|array $receivers
     */
    private function addReceivers($receivers): void
    {
        $receivers = is_string($receivers) && strpos($receivers, ',')
            ? explode(',', $receivers)
            : (array) $receivers;
        $receivers = array_map('trim', $receivers);
        $this->transportBuilder->addTo($receivers);
    }
}

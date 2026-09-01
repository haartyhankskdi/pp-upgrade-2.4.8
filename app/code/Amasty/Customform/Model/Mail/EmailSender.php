<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Mail;

use Amasty\Customform\Model\Template\TransportBuilder;
use Amasty\Customform\Model\Template\TransportBuilderFactory;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Store\Model\StoreManagerInterface;

class EmailSender
{
    /**
     * @var TransportBuilderFactory
     */
    private $transportBuilderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        TransportBuilderFactory $transportBuilderFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->transportBuilderFactory = $transportBuilderFactory;
        $this->storeManager = $storeManager;
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
        $transportBuilder = $this->transportBuilderFactory->create();
        $this->addReceivers($transportBuilder, $receivers);
        $transportBuilder->setTemplateIdentifier($templateIdentifier);
        $this->addTemplateOptions($transportBuilder, $storeId);
        $transportBuilder->setTemplateVars($templateVars);
        $transportBuilder->setFromByScope($sender, $storeId);

        if ($replyTo) {
            $transportBuilder->setReplyTo($replyTo);
        }

        foreach ($attachments as $fileName => $content) {
            $transportBuilder->addAttachment($content, $fileName);
        }

        $transportBuilder->getTransport()->sendMessage();
    }

    private function addTemplateOptions(
        TransportBuilder $transportBuilder,
        ?int $storeId = null,
        ?string $area = Area::AREA_FRONTEND
    ): void {
        if ($storeId === null) {
            $storeId = (int) $this->storeManager->getStore()->getId();
        }

        $transportBuilder->setTemplateOptions(['area' => $area, 'store' => $storeId]);
    }

    /**
     * @param TransportBuilder $transportBuilder
     * @param string|array $receivers
     */
    private function addReceivers(TransportBuilder $transportBuilder, $receivers): void
    {
        $receivers = is_string($receivers) && strpos($receivers, ',')
            ? explode(',', $receivers)
            : (array) $receivers;
        $receivers = array_map('trim', $receivers);

        if (count($receivers) > 1) {
            /*
             * It's done to bypass the Magento 2.3.3 bug, which makes it impossible to add an array
             * of mail recipients until you add one recipient
             */
            $firstReceiver = array_shift($receivers);
            $transportBuilder->addTo($firstReceiver);
        }

        $transportBuilder->addTo($receivers);
    }
}

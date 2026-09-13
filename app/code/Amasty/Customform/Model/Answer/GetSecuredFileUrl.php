<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Answer;

use Amasty\Customform\Api\Answer\GetAttachedFileUrlInterface;
use Amasty\Customform\Model\Utils\DateTime;
use Amasty\Customform\Model\Utils\Encryptor;
use Magento\Framework\UrlInterface;

class GetSecuredFileUrl implements GetAttachedFileUrlInterface
{
    public const AMASTY_CUSTOMFORM_FILE_PATH = 'amasty_customform/form/file';
    public const FILE_PARAM = 'file';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Encryptor
     */
    private $encryptor;

    public function __construct(
        Encryptor $encryptor,
        DateTime $dateTime,
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $fileName
     * @param int|null $storeId
     * @return string
     */
    public function execute(string $fileName, ?int $storeId = null): string
    {
        return $this->urlBuilder->getUrl(
            self::AMASTY_CUSTOMFORM_FILE_PATH,
            [self::FILE_PARAM => $this->encryptor->encryptParams($fileName, $this->dateTime->getCurrentDateTime())]
        );
    }
}

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Utils;

use Magento\Framework\Encryption\Encryptor as MagentoEncryptor;
use Magento\Framework\Serialize\Serializer\Json;

class Encryptor
{
    public const FILE_NAME_KEY = 'file_name';
    public const DATE_KEY = 'date';

    /**
     * @var MagentoEncryptor
     */
    private $encryptor;

    /**
     * @var Json
     */
    private $json;

    public function __construct(
        MagentoEncryptor $encryptor,
        Json $json
    ) {
        $this->encryptor = $encryptor;
        $this->json = $json;
    }

    /**
     * @param string $fileName
     * @param int $date
     * @return string
     */
    public function encryptParams(string $fileName, int $date): string
    {
        $params = [
            self::FILE_NAME_KEY => $fileName,
            self::DATE_KEY => $date,
        ];

        return $this->encryptor->encrypt($this->json->serialize($params));
    }

    /**
     * @param string $encryptedString
     * @return array
     * @throws \Exception
     */
    public function decryptParams(string $encryptedString): array
    {
        return $this->json->unserialize($this->encryptor->decrypt($encryptedString));
    }
}

<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model\Form\File;

use Amasty\Customform\Model\Utils\DateTime;
use Amasty\Customform\Model\Utils\Encryptor;

class ExtractFileName
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Encryptor
     */
    private $encryptor;

    public function __construct(
        DateTime $dateTime,
        Encryptor $encryptor
    ) {
        $this->dateTime = $dateTime;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string|null $encryptedName
     * @return string|null
     * @throws \Exception
     */
    public function execute(?string $encryptedName): ?string
    {
        if ($encryptedName) {
            $params = $this->encryptor->decryptParams($encryptedName);
            if ($this->dateTime->isValidLink($params[Encryptor::DATE_KEY])) {
                $fileName = $params[Encryptor::FILE_NAME_KEY];
            }
        }

        return $fileName ?? null;
    }
}

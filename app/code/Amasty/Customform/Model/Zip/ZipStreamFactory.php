<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Model\Zip;

use Amasty\Customform\Exceptions\ExternalDependencyNotFoundException;
use Amasty\Customform\Model\ExternalLibsChecker;
use Magento\Framework\ObjectManagerInterface;
use ZipStream\Option\Archive as ZipOptions;
use ZipStream\ZipStream;

class ZipStreamFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ExternalLibsChecker
     */
    private $externalLibsChecker;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ExternalLibsChecker $externalLibsChecker
    ) {
        $this->objectManager = $objectManager;
        $this->externalLibsChecker = $externalLibsChecker;
    }

    /**
     * @param string $fileName
     * @return ZipStream
     * @throws ExternalDependencyNotFoundException
     */
    public function create(string $fileName)
    {
        $this->externalLibsChecker->checkZipStream();
        /** @var ZipOptions $options **/
        /** @phpstan-ignore-next-line **/
        $options = $this->objectManager->create(ZipOptions::class);
        $options->setSendHttpHeaders(false);
        $options->setZeroHeader(true);
        $options->setFlushOutput(true);

        /** @phpstan-ignore-next-line **/
        return $this->objectManager->create(ZipStream::class, ['name' => $fileName, 'opt' => $options]);
    }
}

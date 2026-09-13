<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\Model;

use Amasty\Customform\Exceptions\ExternalDependencyNotFoundException as ExternalDependencyNotFoundException;
use ZipStream\Option\Archive as ZipOptions;
use ZipStream\ZipStream;

class ExternalLibsChecker
{
    /**
     * @throws ExternalDependencyNotFoundException
     */
    public function checkZipStream(): void
    {
        if (!class_exists(ZipStream::class)) {
            throw new ExternalDependencyNotFoundException(__(
                'To use ZIP functionality, please install the library maennchen/zipstream-php. ' .
                'To do this, run the command "composer require maennchen/zipstream-php" in the main site folder.'
            ));

        }
    }

    public function getZipStreamMajVersion(): int
    {
        $this->checkZipStream();
        if (class_exists(ZipOptions::class)) {
            return 2;
        }

        return 3;
    }

    public function checkPdfDom(): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new ExternalDependencyNotFoundException(__(
                'To use PDF functionality, please install the library dompdf/dompdf. ' .
                'To do this, run the command "composer require dompdf/dompdf" in the main site folder.'
            ));
        }
    }
}

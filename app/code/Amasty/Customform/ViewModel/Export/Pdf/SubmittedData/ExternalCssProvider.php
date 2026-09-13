<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Custom Form Base for Magento 2
 */

namespace Amasty\Customform\ViewModel\Export\Pdf\SubmittedData;

use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir as Dir;
use Magento\Framework\Module\Dir\Reader;

class ExternalCssProvider
{
    public const EXTERNAL_CSS_PATH = 'base/web/css/external/export/pdf';
    public const EXTERNAL_PDF_STYLES_FILENAME = 'external_styles.css';

    /**
     * @var Reader
     */
    private $directoryReader;

    /**
     * @var File
     */
    private $filesystem;

    public function __construct(
        Reader $directoryReader,
        File $filesystem
    ) {
        $this->directoryReader = $directoryReader;
        $this->filesystem = $filesystem;
    }

    public function getPdfStyles(): string
    {
        $viewDir = $this->directoryReader->getModuleDir(Dir::MODULE_VIEW_DIR, 'Amasty_Customform');
        $filePath = sprintf(
            '%s%s%s%s%s',
            $viewDir,
            DIRECTORY_SEPARATOR,
            self::EXTERNAL_CSS_PATH,
            DIRECTORY_SEPARATOR,
            self::EXTERNAL_PDF_STYLES_FILENAME
        );

        return $this->filesystem->fileExists($filePath)
            ? trim($this->filesystem->read($filePath))
            : '';
    }
}

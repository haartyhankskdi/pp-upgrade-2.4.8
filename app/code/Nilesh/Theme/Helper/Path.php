<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\Theme\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;

class Path extends AbstractHelper
{
    protected $_filesystem;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        DirectoryList $directory_list,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->directory_list = $directory_list;
        $this->_filesystem = $filesystem;
        parent::__construct($context);
    }

    public function mediaPath()
    {
        return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }
    
    public function gqFolderPath()
    {
        return $this->directory_list->getPath('media').'gq_uploads/';
        // return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('gq_uploads/');
    }
}

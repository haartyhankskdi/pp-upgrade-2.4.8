<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Controller\Adminhtml\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use Magento\Framework\Filesystem\DriverPool;

class DownloadDebug extends \Magento\Backend\App\Action
{
    protected $directory_list;
    protected $fileFactory;
    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    private $readFactory;

    /**
     * DownloadDebug constructor.
     * @param Action\Context $context
     * @param DirectoryList $directory_list
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     */
    public function __construct(
        Action\Context $context,
        DirectoryList $directory_list,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory
    ) {
        $this->directory_list = $directory_list;
        $this->fileFactory = $fileFactory;
        $this->readFactory = $readFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $version = $this->getRequest()->getParam('version');
        $filename = "sagepay_debugfile_".$version."_".date("Ymd").".log";
        $file = $this->directory_list->getPath("var")."/log/sagepay/debug.log";
        $fileReader = $this->readFactory->create($file, DriverPool::FILE);
        return $this->fileFactory->create($filename, $fileReader->readAll($file), "tmp");
    }
}

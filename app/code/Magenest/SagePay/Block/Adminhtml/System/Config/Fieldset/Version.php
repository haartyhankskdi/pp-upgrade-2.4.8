<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

namespace Magenest\SagePay\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader as DirReader;

class Version extends Template implements RendererInterface
{
    protected $dirReader;
    protected $directory_list;
    /**
     * @var File
     */
    private $file;
    /**
     * @var ReadFactory
     */
    private $readFactory;

    /**
     * Version constructor.
     * @param DirReader $dirReader
     * @param Template\Context $context
     * @param DirectoryList $directory_list
     * @param File $file
     * @param ReadFactory $readFactory
     * @param array $data
     */
    public function __construct(
        DirReader $dirReader,
        Template\Context $context,
        DirectoryList $directory_list,
        File $file,
        ReadFactory $readFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->directory_list = $directory_list;
        $this->dirReader = $dirReader;
        $this->file = $file;
        $this->readFactory = $readFactory;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        if ($element->getData('group')['id'] == 'version') {
            $html = $this->toHtml();
        }
        return $html;
    }

    /**
     * @return mixed|string
     */
    public function getVersion()
    {
        $installVersion = "unidentified";
        $composer = $this->getComposerInformation("Magenest_SagePay");

        if ($composer) {
            $installVersion = $composer['version'];
        }

        return $installVersion;
    }

    /**
     * @param $moduleName
     * @return false|mixed
     */
    public function getComposerInformation($moduleName)
    {
        $dir = $this->dirReader->getModuleDir("", $moduleName);

        if ($this->file->fileExists($dir.'/composer.json')) {
            $fileReader = $this->readFactory->create($dir.'/composer.json', DriverPool::FILE);
            return json_decode($fileReader->readAll($dir.'/composer.json'), true);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'Magenest_SagePay::system/config/fieldset/version.phtml';
    }

    /**
     * @return string
     */
    public function getDownloadDebugUrl()
    {
        return $this->getUrl('sagepay/config/downloadDebug', ['version'=>$this->getVersion()]);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDebugFilePath()
    {
        return $this->directory_list->getPath("var") . "/log/sagepay/debug.log";
    }
}

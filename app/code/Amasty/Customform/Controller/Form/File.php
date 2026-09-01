<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

declare(strict_types=1);

namespace Amasty\Customform\Controller\Form;

use Amasty\Customform\Model\Answer\GetDownloadPath;
use Amasty\Customform\Model\Answer\GetSecuredFileUrl;
use Amasty\Customform\Model\Form\File\ExtractFileName;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

class File implements HttpGetActionInterface
{
    /**
     * @var ExtractFileName
     */
    private $extractFileName;

    /**
     * @var ForwardFactory
     */
    private $forwardFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var GetDownloadPath
     */
    private $getDownloadPath;

    public function __construct(
        ExtractFileName $extractFileName,
        ForwardFactory $forwardFactory,
        FileFactory $fileFactory,
        GetDownloadPath $getDownloadPath,
        RequestInterface $request
    ) {
        $this->extractFileName = $extractFileName;
        $this->forwardFactory = $forwardFactory;
        $this->request = $request;
        $this->fileFactory = $fileFactory;
        $this->getDownloadPath = $getDownloadPath;
    }

    /**
     * @return ResponseInterface|Forward
     * @throws \Exception
     */
    public function execute()
    {
        $fileName = $this->extractFileName->execute($this->request->getParam(GetSecuredFileUrl::FILE_PARAM));
        if (!$fileName) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $content['type'] = 'filename';
        $content['value'] = $this->getDownloadPath->execute($fileName);

        try {
            $result = $this->fileFactory->create($fileName, $content, DirectoryList::MEDIA);
        } catch (\Exception $e) {
            $result = $this->forwardFactory->create()->forward('noroute');
        }

        return $result;
    }
}

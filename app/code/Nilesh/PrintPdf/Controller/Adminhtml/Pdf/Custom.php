<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrintPdf\Controller\Adminhtml\Pdf;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\App\Filesystem\DirectoryList;

class Custom extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    // *Custom
    protected $_page;
    protected $_style;
    protected $x = 30;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */

     protected $_mediaDirectory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $_rootDirectory;

    /**
     * @var Database
     */
    private $fileStorageDatabase;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Filesystem $filesystem
     * @param Database $fileStorageDatabase
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Database $fileStorageDatabase = null
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->fileFactory = $fileFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_rootDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->fileStorageDatabase = $fileStorageDatabase ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(Database::class);
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $pdf = new \Zend_Pdf();
            $pdf->pages[] = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
            $this->_page = $pdf->pages[0]; // this will get reference to the first page.
            $this->_style = new \Zend_Pdf_Style();
            $this->_style->setLineColor(new \Zend_Pdf_Color_Rgb(0,0,0));
            $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
            $this->_style->setFont($font,15);
            $this->_page->setStyle($this->_style);
            $width = $this->_page->getWidth();
            $hight = $this->_page->getHeight();
            $this->_pageTopalign = 850; //default PDF page height
            $this->y = 850 - 100; //print table row from page top – 100px
            $delimiter = 0;

            // Add Logo
            /* Add image */
            $this->insertLogo($this->_page);

            //Draw table header row’s
            $this->_style->setFont($font,16);
            $this->_page->setStyle($this->_style);
            $this->_page->drawRectangle(30, $this->y + 10, $this->_page->getWidth()-30, $this->y +70, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $this->_style->setFont($font,15);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText(__("Cutomer Details"), $this->x + 5, $this->y+50, 'UTF-8');
            $this->_style->setFont($font,11);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText(__("Name : %1", "John Smith"), $this->x + 5, $this->y+33, 'UTF-8');
            $this->_page->drawText(__("Email : %1","test@example.com"), $this->x + 5, $this->y+16, 'UTF-8');

            //  draw header
            $this->_style->setFont($font,12);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText(__("Products"), $this->x + 60, $this->y-10, 'UTF-8');
            $this->_page->drawText(__("SKU"), $this->x + 200, $this->y-10, 'UTF-8');
            $this->_page->drawText(__("Qty"), $this->x + 310, $this->y-10, 'UTF-8');
            $this->_page->drawText(__("Price"), $this->x + 495, $this->y-10, 'UTF-8');
            $this->_page->drawText(__("Subtotal"), $this->x + 440, $this->y-10, 'UTF-8');

            /* This is for Product list */
            $this->_style->setFont($font,10);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText("hello world", $this->x + 65, $this->y-30, 'UTF-8'); // Set product name
            $this->_page->drawText("hello world", $this->x + 210, $this->y-30, 'UTF-8'); // Set product price
            $this->_page->drawText("hello world", $this->x + 330, $this->y-30, 'UTF-8'); // Set Product qty
            $this->_page->drawText("hello world", $this->x + 470, $this->y-30, 'UTF-8'); // Set product sub total
            $this->_page->drawRectangle(30, $this->y -62, $this->_page->getWidth()-30, $this->y + 10, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            /* This is for product list */
            
            /* This is for Product list */
            $this->_style->setFont($font,10);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText("hello world", $this->x + 65, $this->y-50, 'UTF-8'); // Set product name
            $this->_page->drawText("hello world", $this->x + 210, $this->y-50, 'UTF-8'); // Set product price
            $this->_page->drawText("hello world", $this->x + 330, $this->y-50, 'UTF-8'); // Set Product qty
            $this->_page->drawText("hello world", $this->x + 470, $this->y-50, 'UTF-8'); // Set product sub total
            $this->_page->drawRectangle(30, $this->y -62, $this->_page->getWidth()-30, $this->y + 10, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            /* This is for product list */
            
            $this->_page->drawRectangle(30, $this->y -62, $this->_page->getWidth()-30, $this->y - 100, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
            $this->_style->setFont($font,15);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText(__("Total : %1", "$50.00"), $this->x + 435, $this->y-85, 'UTF-8');
            $this->_style->setFont($font,10);
            $this->_page->setStyle($this->_style);
            $this->_page->drawText(__("ABC Footer example"), ($this->_page->getWidth()/2)-50, $this->y-200);

            $fileName = 'order_'.date("d_m_Y").'.pdf';

            $this->fileFactory->create(
            $fileName,
            $pdf->render(),
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR, // this pdf will be saved in var directory with the name example.pdf
            'application/pdf'
            );
            // return $this->jsonResponse('your response');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }


    /**
     * Insert logo to pdf page
     *
     * @param \Zend_Pdf_Page $page
     * @param string|null $store
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws \Zend_Pdf_Exception
     */
    protected function insertLogo(&$page, $store = null)
    {
        // $this->y = $this->y ? $this->y : 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->fileStorageDatabase->checkDbUsage() &&
                !$this->_mediaDirectory->isFile($imagePath)
            ) {
                $this->fileStorageDatabase->saveFileToFilesystem($imagePath);
            }
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = 850 - 100;
                //top border of the page
                $widthLimit = 175;
                //half of the page width
                $heightLimit = 65;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height; //TOP
                $y2 = $top; // BOTTOM
                $x1 = 25; // LEFT
                $x2 = $x1 + $width; // Right

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }

}


<?php
namespace Fooman\PrintOrderPdf\Controller\Adminhtml\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @copyright  Copyright (c) 2015 Fooman Limited (http://www.fooman.co.nz)
 * @copyright  Copyright © 2015 Magento
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class PrintAction extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Fooman\PrintOrderPdf\Model\Pdf\OrderFactory
     */
    protected $orderPdfFactory;

    /**
     * @param \Magento\Backend\App\Action\Context                $context
     * @param \Magento\Framework\App\Response\Http\FileFactory   $fileFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime        $date
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Fooman\PrintOrderPdf\Model\Pdf\OrderFactory $orderPdfFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->orderRepository = $orderRepository;
        $this->orderPdfFactory = $orderPdfFactory;
        $this->date = $date;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        //echo $orderId;exit();
        // if ($orderId) {
        //     $order = $this->orderRepository->get($orderId);
        //     if ($order->getEntityId()) {
        //         $pdf = $this->orderPdfFactory->create()->getPdf([$order]);
        //         $date = $this->date->date('Y-m-d_H-i-s');
        //         return $this->fileFactory->create(
        //             __('order') . '_' . $date . '.pdf',
        //             $pdf->render(),
        //             DirectoryList::VAR_DIR,
        //             'application/pdf'
        //         );
        //     }
        // }
        // return $this->resultRedirectFactory->create()->setPath('sales/*/view');

        $pdf = new \Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
        $page = $pdf->pages[0]; // this will get reference to the first page.
        $style = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0,0,0));
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font,15);
        $page->setStyle($style);
        $width = $page->getWidth();
        $hight = $page->getHeight();
        $x = 30;
        $pageTopalign = 850; //default PDF page height
        $this->y = 850 - 100; //print table row from page top – 100px
        //Draw table header row’s
        $style->setFont($font,16);
        $page->setStyle($style);
        $page->drawRectangle(30, $this->y + 10, $page->getWidth()-30, $this->y +70, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $style->setFont($font,15);
        $page->setStyle($style);
        $page->drawText(__("Cutomer Details"), $x + 5, $this->y+50, 'UTF-8');
        $style->setFont($font,11);
        $page->setStyle($style);
        $page->drawText(__("Name : %1", "John Smith"), $x + 5, $this->y+33, 'UTF-8');
        $page->drawText(__("Email : %1","test@example.com"), $x + 5, $this->y+16, 'UTF-8');

        $style->setFont($font,12);
        $page->setStyle($style);
        $page->drawText(__("PRODUCT NAME"), $x + 60, $this->y-10, 'UTF-8');
        $page->drawText(__("PRICE"), $x + 200, $this->y-10, 'UTF-8');
        $page->drawText(__("QUANTITY"), $x + 310, $this->y-10, 'UTF-8');
        $page->drawText(__("TOTAL"), $x + 440, $this->y-10, 'UTF-8');

        $style->setFont($font,10);
        $page->setStyle($style);
        $add = 9;
        $page->drawText("$10.00", $x + 210, $this->y-30, 'UTF-8');
        $page->drawText(5, $x + 330, $this->y-30, 'UTF-8');
        $page->drawText("$50.00", $x + 470, $this->y-30, 'UTF-8');
        $pro = "ABC product";
        $page->drawText($pro, $x + 65, $this->y-30, 'UTF-8');
        $page->drawRectangle(30, $this->y -62, $page->getWidth()-30, $this->y + 10, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $page->drawRectangle(30, $this->y -62, $page->getWidth()-30, $this->y - 100, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $style->setFont($font,15);
        $page->setStyle($style);
        $page->drawText(__("Total : %1", "$50.00"), $x + 435, $this->y-85, 'UTF-8');
        $style->setFont($font,10);
        $page->setStyle($style);
        $page->drawText(__("ABC Footer example"), ($page->getWidth()/2)-50, $this->y-200);

        $fileName = 'example.pdf';

        $this->fileFactory->create(
           $fileName,
           $pdf->render(),
           \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR, // this pdf will be saved in var directory with the name example.pdf
           'application/pdf'
        );
    }
}

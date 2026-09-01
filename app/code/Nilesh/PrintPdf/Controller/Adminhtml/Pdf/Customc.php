<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Nilesh\PrintPdf\Controller\Adminhtml\Pdf;
// ini_set('memory_limit', '-1');

use Dompdf\Dompdf;
use Magento\Framework\App\Filesystem\DirectoryList;

class Customc extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;
    // *Custom
    protected $_storeManager;
    protected $_regionFactory;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

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

    protected $barcodeHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Nilesh\PrescriberName\Model\PrescriberNameFactory $prescriberNameFactory,
        \Psr\Log\LoggerInterface $logger,
        \Nilesh\PrintPdf\Helper\Barcode $barcodeHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_rootDirectory = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->orderRepository = $orderRepository;
        $this->_storeManager = $storeManager;
        $this->_countryFactory = $countryFactory;
        $this->_regionFactory = $regionFactory;
        $this->_prescriberNameFactory = $prescriberNameFactory;
        parent::__construct($context);
        $this->barcodeHelper = $barcodeHelper;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // ! HTML Variable
        $html = '';
        
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        try {
            
            $order = $this->orderRepository->get($orderId);
            $productHtml = '';
            // echo "<pre>";
            foreach ($order->getAllVisibleItems() as $item)
            {
                $sizeOp = '';
                $productOption = $item->getProductOptions();
                // echo "<pre>"; var_dump($productOption['attributes_info']); exit();
                if(isset($productOption['attributes_info'])){
                    foreach($productOption['attributes_info'] as $attr){
                        if($attr['label'] == 'Size'){
                            $sizeOp = $attr['value'];
                        }
                    }
                }
                $productHtml .= '<tr>';
                $productHtml .= '<td>'.$item->getName().'</td>';
                $productHtml .= '<td rowspan="2">'.$sizeOp.'</td>';
                $productHtml .= '<td rowspan="2">'.(int) $item->getQtyOrdered().'</td>';
                $productHtml .= '</tr>';
                $productHtml .= '<tr>';
                $productHtml .= '<td style="background-color: #cde9f5;"><i>Directions</i>:</td>';
                $productHtml .= '</tr>';
            }

            $countryShipping = $this->getCountryName($order->getShippingAddress()->getData("country_id"));
            $countryBilling = $this->getCountryName($order->getBillingAddress()->getData("country_id"));
            $dobDate = @date_format(date_create($order->getCustomerDob()),"d/m/Y");
            $createdAt = @date_format(date_create($order->getCreatedAt()),"d/m/Y");

            // Prescriber name and Address
            $prescriberNameId = $order->getData("prescriber_name");
            $model = $this->_prescriberNameFactory->create();
            $collection = $model->load($prescriberNameId);
            $prescriberName = $collection->getName();
            $prescriberNameAddr = 'Unit 4 Progress Business Centre, Slough, Berkshire SL1 6DQ';
            $prescriberSignature = $collection->getData('Comment');
            // $prescriberSignature = "Nilesh Dubey";

            // var_dump($order->getData()); exit();

            // * DOMPDF start here
            $barcodeBase64 = $this->barcodeHelper->generateBarcodeBase64(
                $order->getIncrementId(),
                2,   // bar width
                60   // bar height
            );
            $dompdf = new Dompdf();

            $html .='<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Document</title>
<style>


        /* Font Lucida Handwriting */

        @font-face {
        font-family: \'lucida handwriting\';
        src: url(\'/fonts/Lucida-Handwriting-Italic.ttf\') ;
        }

    .product{
        margin-top:20px;
    }
    .product table{
        margin-bottom:25px;
    }
    .product table td{
        border:1px solid #222;
        padding: 10px 5px;
        color:#222;
    }
    .product table th{
        padding-bottom:5px;
        border-bottom:3px double #222; 
        font-size:14px;
        color:#222;
    }
    .sideAddress{
        font-size: 14px !important;
        font-weight: 400;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 20px;
        text-align:right;
        color:#000;
    }
    .sideAddress p{
        font-size: 14px !important;
        font-weight: 400;
        font-family: Arial, Helvetica, sans-serif;
        line-height: 20px;
        color:#000;
    }
    .sideAddress p a{
        text-decoration: underline !important;
        color:#3398ca !important;
    }
    .dark_header{
        text-align: center;
        background: #44546A;
        margin-bottom: 20px;
        margin-top: 20px;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 24px !important;
        color: #fff;
    }
    .dark_header p{
        padding-top: 3px;
        padding-bottom: 3px;
    }
    .notes p span{
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bold;
        font-size: 13px;
        color:#222;
    }
    .notes p span.underline{
       border-bottom:1px solid #222;
    }
    .pres_wrapper p{
        color:#222;
    }
    .pres_wrapper p span{
        padding-bottom:2px;
        line-height:20px;
        color:#222;
    }
    .pres_wrapper p span.underline{
        border-bottom:1px solid #222;
    }
    .sideAddress p a[href^="mailto"] { color: #53caf5; border-bottom:1px solid #53caf5;}
</style>
            </head><body>';

            $html .='<section class="header" style="width:100%">
                    <div class="wrapper">
                    <div class="image_logo" style="float: left; width:450px; text-align:left; ">
                        <img src="'.$this->getDataImage($this->getLogoImage($order->getStore()->getId())).'"  width="225" style="margin-left:0px; margin-top:30px; float:left;">
                    </div>
                    <div class="sideAddress">
                        <p style="font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px;">
                            '.$this->getHeaderAddressFormate($order->getStore()->getId()).'
                        </p>
                    </div>
                    <div class="dark_header" >
                        <p>PRIVATE PRESCRIPTION</p>
                    </div>
                </section>
                <section class="address">
                    <table>
                        <tr>
                            <td class="left">
                                <table style="margin-right:50px; vertical-align: top;">
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 3px; padding-bottom: 10px; color: #000; vertical-align:top;position: relative; top: 2px;">RX Details:<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 3px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span class="sline-one" style="border-bottom: 1px solid #000; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData('firstname')." ".$order->getShippingAddress()->getData('lastname').'</span><br />
                                        <span class="sline-one" style="border-bottom: 1px solid #000; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData("street").'</span><span class="sline-two" style="border-bottom: 1px solid #000; padding-bottom: 2px; padding-top: 12px; width: 100%; display:block;">'.$order->getShippingAddress()->getData("region").', '.$order->getShippingAddress()->getData("city").'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; color: #000;position: relative; top: 2px;">Postcode<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData("postcode").'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; color: #000; position: relative; top: 2px;">Country<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$countryBilling.'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px;float:left; color: #000;position: relative; top: 2px;">Ref<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 70px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style="color:red; border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getIncrementId().'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 10px; padding-bottom: 10px;float:left; color: #000; height:15px;"></td>
                                        <td style="width: 70px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;height:15px;"></td>
                                        <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px;float:left; color: #000;position: relative; top: 2px;">DOB<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 115px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$dobDate.'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; float:left; color: #000;position: relative; top: 2px;">Date<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 115px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px; float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$createdAt.'</span></td>
                                    </tr>    
                                    </tr>
                                </table>
                            </td>
                            <td class="right">
                                <table>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 3px; padding-bottom: 10px; color: #000; vertical-align:top;position: relative; top: 2px;">Ship  To<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 3px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span class="sline-one" style="border-bottom: 1px solid #000; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData('firstname')." ".$order->getShippingAddress()->getData('lastname').'</span><br /><span class="sline-one" style="border-bottom: 1px solid #000; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData("street").'</span><span class="sline-two" style="border-bottom: 1px solid #000; padding-bottom: 2px; padding-top: 12px; width: 100%; display:block;">'.$order->getShippingAddress()->getData("region").', '.$order->getShippingAddress()->getData("city").'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; color: #000;position: relative; top: 2px;">Postcode<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$order->getShippingAddress()->getData("postcode").'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; color: #000;position: relative; top: 2px;">Country<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 215px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$countryShipping.'</span></td>
                                    </tr>


                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400;
                                            font-size: 14px; padding-top: 6px; padding-bottom: 10px; color: #000;
                                            vertical-align: top; position: relative; top: 2px;">
                                            Barcode<span style="float:right; padding-right:10px;">:</span>
                                        </td>
                                        <td style="width: 215px; padding-top: 7px; padding-bottom: 6px; vertical-align: top;">

                                            <!-- PDF417 Barcode -->
                                            <img src="'.$barcodeBase64.'" style="width:200px; height:80px; display:block;" />

                                            <!-- Order ID below barcode -->
                                            <span style="font-size:9px; color:#333; display:block;
                                                text-align:center; margin-top:3px; letter-spacing:2px;">
                                                '.$order->getIncrementId().'
                                            </span>

                                        </td>
                                    </tr>


                                    <!--  tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px;float:left; color: #000;position: relative; top: 2px;">DOB<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 115px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px;float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$dobDate.'</span></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 80px; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px; padding-top: 6px; padding-bottom: 10px; float:left; color: #000;position: relative; top: 2px;">Date<span style="float:right;padding-right:10px;">:</span></td>
                                        <td style="width: 115px; border-bottom: 0px solid #222; text-align: center;padding-top: 7px; padding-bottom: 10px; float:left;font-family: Arial, Helvetica, sans-serif;font-size: 14px;"><span style=" border-bottom: 1px solid #222; padding-bottom: 2px; width: 100%; display: inline-block;">'.$createdAt.'</span></td -->
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </section>
                <section class="product">
                    <table cellspacing="0" cellpadding="0" style="width: 100%; font-family: Arial, Helvetica, sans-serif; font-weight: 400; font-size: 14px;">
                        <tr>
                            <th style="text-align: left;font-weight: bold; font-size: 14px;color: #000;">Product:</th>
                            <th style="text-align: left;font-weight: bold; font-size: 14px;color: #000;">Pack Size:</th>
                            <th style="text-align: left;font-weight: bold; font-size: 14px;color: #000;">Quantity:</th>
                        </tr>
                        <!-- Product list -->

                        '.$productHtml.'

                        <!-- Product list -->
                    </table>
                </section>

                <section class="prescriber_name">
                    <div class="notes" style="display:none">
                        <p><span>Notes: </span><span style:border-bottom:1px solid #222;></span></p>
                    </div>
                    <div class="pres_wrapper">
                        <p style:padding:0; margin:0;><span style="font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px;  width:230px; padding-bottom:2px; display:inline-block; margin-right:15px;color: #000;">Prescriber\'s Name<span style="float:right;padding-right:10px;">:</span> </span><span class="underline" style="width:450px; padding-bottom:0px; line-height:8px; display:inline-block; text-align:left; font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; padding-right:0px;"><span class="pre-name-left" style="text-align:left; width:140px; display:inline-block; line-height:8px;">'. $prescriberName .'</span><span class="pre-name-right" style="width:310px; text-align:right; padding-left:0px; line-height:8px; display:inline-block;">Pharmacist Independent Prescriber</span></span></p>

                        <p style:padding:0; margin:0;><span style="font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; width:230px; padding-bottom:2px; display:inline-block; margin-right:15px;color: #000;">Prescriber\'s Address<span style="float:right;padding-right:10px;">:</span> </span><span class="underline" style="width:450px; padding-bottom:2px; line-height:18px; display:inline-block;  text-align:left; font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px;">'. $prescriberNameAddr .'</span></p>

                        <p style:padding:0; margin:0;><span style="font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; width:230px; padding-bottom:2px; display:inline-block;margin-right:15px;color: #000;">Prescriber\'s Registration Number<span style="float:right;padding-right:10px;">:</span> </span><span class="underline" style="width:450px; padding-bottom:2px; line-height:18px; display:inline-block;  text-align:left;font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; ">Enter Reg No</span></p>
                    </div>
                </section>
                <section class="footer">
                    <div class="image_wrapper" >
                        <p style:padding:0; margin:0;><span style="font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; width:230px; padding-bottom:2px; display:inline-block;margin-right:15px;color: #000;">Prescriber\'s Signature<span style="float:right;padding-right:10px;">:</span> </span><span class="underline" style="width:450px; padding-bottom:2px; line-height:24px; display:inline-block;  text-align:left;font-family: lucida handwriting, cursive; font-size: 22px; color: #000; font-weight: 700; font-style:italic;">'. $prescriberSignature .'</span></p>

        <!--<p style:padding:0; margin:0;><span style="font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 14px; width:230px; padding-bottom:2px; display:inline-block;margin-right:15px;color: #000;"></span><span class="underline" style="width:450px; padding-bottom:2px; padding-top:20px; line-height:18px; display:inline-block;  text-align:left;font-family: Arial, Helvetica, sans-serif;
        font-weight: 400; font-size: 24px; color: #ddd; font-style: italic;">ENTER SIGNATURE</span></p>-->
                        <!-- Image -->
                    </div> 
                    <p style="font-family: Arial, Helvetica, sans-serif; font-weight: 4; font-size: 14px; padding-bottom:2px; padding-top:18px; color: #000; display:none; "><span>Date: </span><span class="underline" style:border-bottom:1px solid #222; padding-bottom:2px;></span></p>
                </section>
            </div>';

            $html .= '</body></html>';

            // echo $html; exit();

            $dompdf->loadHtml($html);

            // ! (Optional) Setup the paper size and orientation
            // $dompdf->setPaper('A4', 'landscape');
            $dompdf->setPaper('A4');

            // Render the HTML as PDF
            $dompdf->render();

            // ! Output the generated PDF to Browser
            // $dompdf->stream();
            //$dompdf->stream("Order_".$order->getCustomerFirstname()."_".$order->getCustomerLastname()."_".date('d_m_Y').".pdf");
            $dompdf->stream("RX_".$order->getIncrementId().".pdf");
            // * DOMPDF END HERE
            return;
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

    private function getLogoImage($store = null)
    {
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $imagePath = '/sales/store/logo/' . $image;
        return $this->_mediaDirectory->getAbsolutePath($imagePath);
    }

    public function getDataImage(String $image)
    {
        $type = pathinfo($image, PATHINFO_EXTENSION);
        $data = file_get_contents($image);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    public function getHeaderAddressFormate($store = null)
    {
        $html = '';
        $street = $this->_scopeConfig->getValue(
            'general/store_information/street_line1',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $street2 = $this->_scopeConfig->getValue(
            'general/store_information/street_line2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $city = $this->_scopeConfig->getValue(
            'general/store_information/city',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $postcode = $this->_scopeConfig->getValue(
            'general/store_information/postcode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $country_id = $this->_scopeConfig->getValue(
            'general/store_information/country_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $region_id = $this->_scopeConfig->getValue(
            'general/store_information/region_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $phone = $this->_scopeConfig->getValue(
            'general/store_information/phone',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $ident_sales = $this->_scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $country = $this->getCountryName($country_id);
        $region = $this->getRegionName($region_id);

        $html .= $street."<br />"; // Line 1
        $html .= $street2."<br />"; // Line 2
        // $html .= ($city)?$city.", ":""; // line 3
        // $html .= ($region)?$region.", ":""; // line 3
        $html .= ($postcode)?$postcode."":""; // line 3
        // $html .= ($country)?$country.", ":""; // line 3
        $html .= "<br />";
        $html .= "Tel/Fax: ". $phone ."<br />"; // Line 4
        $html .= '<a href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a><br />'; // Line 5
        $html .= 'Email:'.$ident_sales; // Line 6

        return $html;
    }

    public function getBaseUrl($store = null)
    {
        return $this->_storeManager->getStore($store)->getBaseUrl();
    }

    public function getCountryName($countryCode = "GB")
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    public function getRegionName($regioncode = '')
    {
        if($regioncode){
            $region = $this->_regionFactory->create()->load($regioncode);
            return $region->getName();
        }
        return '';
    }

    public function remove_http($url) {
        $disallowed = array('http://', 'https://');
        foreach($disallowed as $d) {
            if(strpos($url, $d) === 0) {
                $newUrl = str_replace($d, '', $url);
                if($newUrl){
                    return rtrim($newUrl,"/");
                }
            }
        }
        return $url;
    }
}

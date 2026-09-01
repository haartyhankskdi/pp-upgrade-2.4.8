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
use Nilesh\GeneralQuestions\Model\ResourceModel\GeneralQuestions\Collection as GeneralQuestions;

class Gppdf extends \Magento\Backend\App\Action
{
    protected $generalQuestions;

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
        GeneralQuestions $generalQuestions,
        \Psr\Log\LoggerInterface $logger
        ) {
        $this->generalQuestions = $generalQuestions;
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

        $store__id = $order->getStore()->getId();

        $phone = $this->_scopeConfig->getValue(
            'general/store_information/phone',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store__id
        );

        $ident_sales = $this->_scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store__id
        );

        try {

            $order = $this->orderRepository->get($orderId);

            // if($order->getCustomerIsGuest()) return '';

            $countryShipping = $this->getCountryName($order->getShippingAddress()->getData("country_id"));
            $countryBilling = $this->getCountryName($order->getBillingAddress()->getData("country_id"));
            $dobDate = @date_format(date_create($order->getCustomerDob()),"d/m/Y");
            $createdAt = @date_format(date_create($order->getCreatedAt()),"d/m/Y");

            $customerId = $order->getCustomerId();

            // Prescriber name and Address
            $prescriberNameId = $order->getData("prescriber_name");
            $model = $this->_prescriberNameFactory->create();
            $collection = $model->load($prescriberNameId);
            $prescriberName = $collection->getName();
            $prescriberNameAddr = $collection->getData('Comment');

            // var_dump($order->getData()); exit();

            $productHtml = '';
            $productCount = count($order->getAllItems());
            $productInc = 1;
            // echo "<pre>";
            foreach ($order->getAllItems() as $item)
            {
                if($item->getParentItemId() != null){
                    if($productCount == $productInc){
                        $productHtml .= '<span>'.$item->getName().'</span>';
                    }else{
                        $productHtml .= '<span>'.$item->getName().', </span>';
                    }
                }
                $productInc++;
            }

            // * DOMPDF start here
            $dompdf = new Dompdf();

            $html .='<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Document</title>
<style>

.page_break { page-break-before: always; }

    body{
	padding: 0;
	margin: 0;
}
.main-section1{
	width: 100%;
    display: block;
	padding: 0;
	margin: 0;
}
.top-left{
	width: auto; 
	padding: 0;
	margin: 0;
}
.top-right{
	width: auto; 
	padding: 0;
	margin: 0;
}
.address{
	width: 300px;
	display: block;
	padding: 0;
	margin: 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
.address p{
	padding: 0;
	margin: 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000; 
}
.address p a{
	color:#000;
	text-decoration: none;
}
.main-content{
	width: 100%;
	display: block;
	margin-top: 50px;
}
.main-content p{
	width: 100%; 
	padding: 0;
	margin: 0 0 15px 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000; 
}
.patient-details{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 30px 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
.patient-details table td{
	line-height: 28px;
}
p{
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
h2{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 20px 0;
	font-size: 25px !important;
        font-weight: 500;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 24px;
        text-align:center;
        color:#0DA4DD;
}
h3{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 5px 0;
	font-size: 13px !important;
        font-weight: 700;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 18px;
        text-align:left;
        color:#f00;
        font-style: italic;
}
h4{
	
	padding: 0;
	margin: 0 0 5px 0;
	font-size: 14px !important;
        font-weight: 700;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 18px;
        text-align:center;
        color:#000;
        font-style:italic;
}

.main-section1{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0;
}
.top-left{
	width: auto;
	display: block;
	padding: 0;
	margin: 0;
}
.top-right{
	width: auto;
	display: block;
	padding: 0;
	margin: 0;
}
.address{ 
	padding: 0;
	margin: 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
.address p{
	padding: 0;
	margin: 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000; 
}
.address p a{
	color:#000;
	text-decoration: none;
}
.main-content{
	width: 100%;
	display: block;
	margin-top: 50px;
}
.main-content p{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 15px 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000; 
}
.patient-details{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 30px 0;
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
.patient-details table td{
	line-height: 22px;
}
p{
	font-size: 11px !important;
        font-weight: 400;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 16px;
        text-align:left;
        color:#000;
}
h2{
	width: 100%;
	display: block;
	padding: 0;
	margin: 0 0 20px 0;
	font-size: 25px !important;
        font-weight: 500;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 24px;
        text-align:center;
        color:#0DA4DD;
}
h3{
    width: 100%;
    display: block;
    padding: 0;
    margin: 0 0 5px 0;
    font-size: 13px !important;
        font-weight: 700;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 18px;
        text-align:left;
        color:#f00;
        font-style: italic;
}
h4{
    
    padding: 0;
    margin: 0 0 5px 0;
    font-size: 14px !important;
        font-weight: 700;
        font-family: Calibri, Helvetica, sans-serif;
        line-height: 18px;
        text-align:center;
        color:#000;
        font-style:italic;
}
.footer-section{
    width: 100%;
    display: block;
}

</style>
            </head><body>';

            $html .='<div class="main-section1">
		<div class="top-1">
        <div>
        <table style="width: 100%; font-size:10px !important; ">
    <tr>
        <td style="float:left;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("logo.jpg")).'" alt=""  /></td>

        
    
    <td style="text-align:left;width:170px; vertical-align:top;">
                <p style="font-size:10px !important; line-height:14px;"><span style="float:left; margin-top:12px; margin-left:-15px; margin-right:5px;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("location-icon.jpg")).'" alt=""  /></span>
                 Unit 4 Progress Business Centre<br/>
                    Whittle Parkway<br/>
                    Slough<br/>
                    Berkshire<br/>
                    SL1 6DQ</p>
            </td>
    <td style="text-align:left; font-size:10px !important; vertical-align:top; width:210px;">
    
                <p style="font-size:10px !important; line-height:14px; margin-bottom:10px;"><span style="margin-top:15px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("phone-icon-pdf.jpg")).'" alt="" /></span> <a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a></p>
                <p style="font-size:10px !important; line-height:14px;margin-bottom:10px;"><span style="margin-top:10px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("email-icon-pdf.jpg")).'" alt="" /></span> <a href="mailto:headoffice@pharmacyplanet.com" style="font-size:10px !important; color:#000;
    text-decoration: none;">headoffice@pharmacyplanet.com</a></p>
                <p style="font-size:10px !important; line-height:14px;margin-bottom:10px;"><span style="margin-top:10px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("web-icon-pdf.jpg")).'" alt="" /></span> <a style="font-size:10px !important; color:#000;
    text-decoration: none;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a></p>
            </td>
    </tr>
    </table>
        </div> 
         
    </div>

<div style="clear:both;"><br/><br/></div>
	<div class="main-content" style=" float:none; width:85%; margin:0 auto;">
	<p>'.$this->getGPSurgery($customerId).'</p>

	<p><strong>Date:</strong></p>
	<p>Dear Doctor, <br/><br/>
    Your patient was recently reviewed by one of Pharmacy Planet’s prescribers during an online consultation. A private prescription was written and dispensed for them and the medicine subsequently supplied. We are informing you of this for your records. This is not a prescription request.</p>
	<p>Please find below details of the consultation and the medicine(s) supplied.</p>


	<div class="patient-details" style="display: inline-block; width:100%;">
		<table style="width: 100%; border: 1px solid #ddd; padding: 10px 30px;">
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Consultation #</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">'.$order->getIncrementId().'</td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Patient</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">'.$order->getShippingAddress()->getData('firstname')." ".$order->getShippingAddress()->getData('lastname').'</td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>DOB</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">'.$dobDate.'</td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Address</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;"><span class="sline-one"  >'.$order->getShippingAddress()->getData("street").'</span><span class="sline-two"  >'.$order->getShippingAddress()->getData("region").', '.$order->getShippingAddress()->getData("city").'</span> <span class="countryShipping">, '.$countryShipping.'</span><span>'.$order->getShippingAddress()->getData("postcode").'</span>
                <br/><span style="font-size:9px !important;">Please note – this address may differ from your records</span>
                </td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Date of Supply</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">'.$createdAt.'</td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Medicine</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">'.$productHtml.'</td>
			</tr>
			<tr>
				<td style="width: 130px; vertical-align:top;"><strong>Directions</strong><span style="float:right; margin-right:15px;">:<span></td>
				<td style=" vertical-align:top;">  </td>
			</tr>

		</table>
	</div>

	<p style="display:block; width:100%;">Please see overleaf for further information about Pharmacy Planet and our online consultation process. For further information please visit our website at <a style="color:#0DA4DD;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a> If you have concerns regarding this supply, please contact us on <a href="mailto:headoffice@pharmacyplanet.com" style="color:#0DA4DD;">headoffice@pharmacyplanet.com</a> or by phone on <a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a>.</p>

	<p><br/><br/>Yours Sincerely,<br/><br/></p>
	<p>Gurdev Sehmi<br/>
	<span style="font-size: 8px !important;">BSc Pharm, MRPharmS, Independent Prescriber</span><br/>
	Clinical Lead<br/>
	Pharmacy Planet <br/><br/><br/><br/><br/></p>

	</div>



	<div class="footer-section" style=" float:none; width:85%; margin:20px auto 0 auto;">
        <div style="width: 170px; float: left; padding: 0; margin: 0;"><a href="https://uk.trustpilot.com/review/pharmacyplanet.co.uk?utm_medium=trustbox&utm_source=MicroCombo" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("trustpilot-img.jpg")).'" alt="" /></a></div>
        <div style="width: 270px; float: left; padding: 0; margin: 0;"><span style="float: left;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("capsul-img.jpg")).'" alt="" /></span>
            <p style="text-decoration: underline; margin-bottom: 0px; padding: 0;
    line-height: 20px;
    margin-top: 0;
    text-align: center;"><a style="color:#0DA4DD;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a></p>
            <p style="text-align: center; margin: 0;"><a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a></p>
        </div>
        <div style="width: auto; float: right; padding: 0; margin: 0 0 0 40px;"><a href="https://www.pharmacyregulation.org/registers/pharmacy/registrationnumber/1035466" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("pharmacy-logo.jpg")).'" alt="" /></a></div>
        <div style="width: auto; float: right; padding: 0; margin: 0 0 0;"><a href="https://medicine-seller-register.mhra.gov.uk/search-registry/386" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("mhra-logo.jpg")).'" alt="" /></a></div>

    </div>

    </div>

    <div class="page_break"></div>

    <div class="main-section1">
    
		<div class="top-1">
        <div>
        <table style="width: 100%; font-size:10px !important; ">
    <tr>
        <td style="float:left;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("logo.jpg")).'" alt=""  /></td>

        
    
    <td style="text-align:left;width:170px; vertical-align:top;">
                <p style="font-size:10px !important; line-height:14px;"><span style="float:left; margin-top:12px; margin-left:-15px; margin-right:5px;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("location-icon.jpg")).'" alt=""  /></span>
                 Unit 4 Progress Business Centre<br/>
                    Whittle Parkway<br/>
                    Slough<br/>
                    Berkshire<br/>
                    SL1 6DQ</p>
            </td>
    <td style="text-align:left; font-size:10px !important; vertical-align:top; width:210px;">
    
                <p style="font-size:10px !important; line-height:14px; margin-bottom:10px;"><span style="margin-top:15px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("phone-icon-pdf.jpg")).'" alt="" /></span> <a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a></p>
                <p style="font-size:10px !important; line-height:14px;margin-bottom:10px;"><span style="margin-top:10px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("email-icon-pdf.jpg")).'" alt="" /></span> <a href="mailto:headoffice@pharmacyplanet.com" style="font-size:10px !important; color:#000;
    text-decoration: none;">headoffice@pharmacyplanet.com</a></p>
                <p style="font-size:10px !important; line-height:14px;margin-bottom:10px;"><span style="margin-top:10px; "><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("web-icon-pdf.jpg")).'" alt="" /></span> <a style="font-size:10px !important; color:#000;
    text-decoration: none;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a></p>
            </td>
    </tr>
    </table>
        </div> 
         
    </div>


<div style="clear:both;"><br/><br/></div>
        <div class="main-content" style=" float:none; width:85%; margin:0 auto;">
    		 <h2>Further Information for GPs</h2>
			<h3><strong>Who are Pharmacy Planet?</strong></h3>
			<p style="line-height:14px; margin-bottom:10px;">Pharmacy Planet is an online private pharmacy specialising in the supply of repeat medicines.<br/><br/>
			The advent of the internet has facilitated multitudinous innovations that have revolutionised nearly every aspect of our lives. At Pharmacy Planet we aim to harness these technological advances to be able to offer our patients new and innovative ways to access pharmaceutical care and services. </p>

			<h3><strong>How does it work?</strong></h3>
			<p style="line-height:14px; margin-bottom:10px;">Using our website, patients are able to make a medication request via an online clinical consultation process. Their request is forwarded to one of our in-house prescribers who reviews the patient and the information gleaned during the clinical consultation. If satisfied the prescriber will issue a private prescription in line with the patient’s request and the prescriber’s assessment of its clinical appropriateness. The prescription is forwarded to our dispensary for assembly and checking before their medicine is dispatched to the patient via courier. </p>
			<h3><strong>Why am I receiving this letter? </strong></h3>
			<p style="line-height:14px; margin-bottom:10px;">One of your patients has recently made such a medication request to Pharmacy Planet and has been assessed and supplied with prescription medicine. A private prescription was written and dispensed for them based on our prescriber’s assessment of the patient and their online consultation. We are informing you for your records. </p>
			<p style="line-height:14px; margin-bottom:10px;"><strong>Please note – this is NOT a request for a prescription. On this occasion your patient has elected to source their medicine independently of the NHS and as such, they have incurred the full cost of obtaining their medicine. This letter has been sent to keep you informed of the patients continuing access to medicine. </strong></p>
			<h3>Why would my patient use Pharmacy Planet?</h3>
			<p style="line-height:14px; margin-bottom:10px;">Patients choose Pharmacy Planet for varying reasons. Typically, patients access medication from Pharmacy Planet which they cannot source from their local pharmacy due to stock issues or NHS brand restrictions. Traditionally, the local GP and the local pharmacy were the only way to access healthcare and medication in the UK. Nowadays consumers not only expect to be able to access pharmaceutical services online, they also wish to have greater choice and ultimately have greater control of their medication or medical conditions. Pharmacy Planet delivers an efficient and convenient means of accessing a wide range of pharmaceutical products delivered wherever you happen to be. </p>
			<h3>How do I find out more? </h3>
			<p style="line-height:14px; margin-bottom:10px;">Visit <a style="color:#0DA4DD;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a> for more information or for specific queries contact us by email at
<a href="mailto:headoffice@pharmacyplanet.com" style="color:#0DA4DD;">headoffice@pharmacyplanet.com</a> or by phoning <a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a>. </p>
<p>Pharmacy Planet and its associated premises are registered with and regulated by the General Pharmaceutical Council (GPhC) in the UK. </p>

<div style="text-align: center; border: 1px solid #ddd; padding:10px 20px 0 20px; margin-top: 25px; margin-bottom: 40px;">
	<h4 style="width:395px; margin:0 auto 10px auto; padding: 0 5px; background-color: #FFFF00;">Are your patients having difficulty sourcing medication?</h4>
	<p style="line-height:14px; margin-bottom:10px; text-align:center;">Due to supply chain issues many patients are having difficulty sourcing medication from their local pharmacy.
Pharmacy Planet specialises in medication which is difficult to source. We use a variety of suppliers and can often
obtain stock that some other pharmacies cannot. </p>


</div>
<br/><br/>

		</div>

	<div class="footer-section" style=" float:none; width:85%; margin:20px auto 0 auto;">
        <div style="width: 170px; float: left; padding: 0; margin: 0;"><a href="https://uk.trustpilot.com/review/pharmacyplanet.co.uk?utm_medium=trustbox&utm_source=MicroCombo" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("trustpilot-img.jpg")).'" alt="" /></a></div>
        <div style="width: 270px; float: left; padding: 0; margin: 0;"><span style="float: left;"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("capsul-img.jpg")).'" alt="" /></span>
            <p style="text-decoration: underline; margin-bottom: 0px; padding: 0;
    line-height: 20px;
    margin-top: 0;
    text-align: center;"><a style="color:#0DA4DD;" href="'.$this->getBaseUrl().'">'.$this->remove_http($this->getBaseUrl()).'</a></p>
            <p style="text-align: center; margin: 0;"><a href="tel:+44 (0)800 978 8956" style="color:#000;
    text-decoration: none;">'. $phone .'</a></p>
        </div>
        <div style="width: auto; float: right; padding: 0; margin: 0 0 0 40px;"><a href="https://www.pharmacyregulation.org/registers/pharmacy/registrationnumber/1035466" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("pharmacy-logo.jpg")).'" alt="" /></a></div>
        <div style="width: auto; float: right; padding: 0; margin: 0 0 0;"><a href="https://medicine-seller-register.mhra.gov.uk/search-registry/386" target="_blank"><img src="'.$this->getDataImage($this->getPdfUsedStaticImages("mhra-logo.jpg")).'" alt="" /></a></div>

    </div>



	</div>
    ';

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
            $dompdf->stream("GP_".$order->getCustomerFirstname()."_".$order->getCustomerLastname()."_".date('d_m_Y').".pdf");
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

    public function getPdfUsedStaticImages($imageName)
    {
        $imagePath = '/printPdf/' . $imageName;
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

    /* 
        @return General Question detail
    */

    public function getGPSurgery($customerId = '')
    {
        /**
         * Step to do so
         * 1. Need customer id
         * 2. Need to get custom question
         */
        if(empty($customerId)) return 'qwe';
        $model = $this->generalQuestions->addFieldToFilter("customer_id", $customerId)->setPageSize(1)->setOrder('generalquestions_id', 'DESC')->load();
        $gqData = $model->getData();
        $gq = $gqData[0] ?? [];
        if(!empty($gq) && $gq['customer_id'] == $customerId){
            if(isset($gq['registered_gp']) && ($gq['registered_gp'] == 1) && isset($gq['registered_gp_permission']) && ($gq['registered_gp_permission'] == 1) && isset($gq['registered_gp_surgery'])){
                // return ?$gq['registered_gp_surgery']:'';
                // . $gpSurg->name_of_practice .'<br/>'
                $gpSurg =  json_decode($gq['registered_gp_surgery']);
                $docHtml =  '
                    <span class="name_of_practice">'.$gpSurg->address_line_one .'<br/>'. $gpSurg->address_line_two .'<br/>'. $gpSurg->city .', '. $gpSurg->county .'<br/>'. $gpSurg->postcode .'</span> 
                    ';
                return $docHtml;
            }
        }
        return 'PRACTICE NAME<br/>
		ADDRESS LINE 1<br/>
		ADDRESS LINE 2<br/>
		CITY<br/>
		POSTCODE';
    }

}

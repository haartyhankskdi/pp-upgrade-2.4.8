<?php  
namespace Kdi\Testimonials\Controller\Index;  

use Magento\Framework\App\Action\Action; 
use Magento\Framework\App\Action\Context; 
use Magento\Framework\View\Result\PageFactory; 
use Kdi\Testimonials\Model\TestimonialsFactory; 
use Magento\Framework\App\RequestInterface;  

class Index extends Action {     
    /** @var PageFactory */     
    protected $pageFactory;      

    /** @var TestimonialsFactory */     
    protected $modelFactory;      

    /** @var RequestInterface */     
    protected $request;      

    protected $resultFactory;     
    protected $_messageManager;      

    /**      
     * Constructor      
     *      
     * @param Context $context      
     * @param PageFactory $pageFactory      
     * @param TestimonialsFactory $testimonialsFactory      
     */     
    public function __construct(         
        Context $context,         
        PageFactory $pageFactory,         
        TestimonialsFactory $testimonialsFactory,         
        RequestInterface $request,         
        \Magento\Framework\Controller\ResultFactory $resultFactory,         
        \Magento\Framework\Message\ManagerInterface $messageManager     
    ) {         
        $this->request = $request;         
        $this->pageFactory = $pageFactory;         
        $this->modelFactory = $testimonialsFactory;         
        $this->resultFactory = $resultFactory;         
        $this->_messageManager = $messageManager;         
        parent::__construct($context);     
    }      

    /**      
     * Execute method      
     *      
     * @return \Magento\Framework\View\Result\Page      
     */     
    public function execute() {         
        $resultPage = $this->pageFactory->create();    
        $this->setPageMetaData($resultPage);     
        return $resultPage;     
    }      

    /**      
     * Retrieve post details      
     *      
     * @return \Kdi\AdvisePost\Model\AdvicePost|null      
     */     
    private function getPostDetails() {         
        $id = $this->request->getParam('id');         
        $model = $this->modelFactory->create()->load(1);         

        return $model->getId() ? $model : null;     
    }      

    /**      
     * Set meta data for the page      
     *      
     * @param \Magento\Framework\View\Result\Page $resultPage      
     * @param \Kdi\AdvisePost\Model\Testimonials $testimonials      
     */     
    private function setPageMetaData($resultPage) {         
        $config = $resultPage->getConfig();          

        // Set page title         
        $config->getTitle()->set('Weight Loss Success Stories | Real Results from Pharmacy Planet UK');          

        // Set meta description         
        $config->setDescription('See real weight loss success stories and before & after results from Pharmacy Planet patients. Achieve safe, effective weight loss with pharmacy-led support today.');          

        // Set meta robots         
        $config->setMetadata('robots', 'index, follow');      
        
        $config->addRemotePageAsset(
            'https://www.pharmacyplanet.com/successstory/',
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );

        // Set Open Graph meta tags         
        $this->setOpenGraphMetaData($config);     
    }      

    /**      
     * Set Open Graph meta data      
     *      
     * @param \Magento\Framework\View\Page\Config $config          
     */     
    private function setOpenGraphMetaData($config) {         
        $baseUrl = 'https://phpstack-732705-2678695.cloudwaysapps.com/static/frontend/Custom/luma_child/en_GB/images/phramacy_planet.svg';         
        $customUrl = 'https://www.pharmacyplanet.com/successstory/';          

        $config->setMetadata('og:title', 'Weight Loss Success Stories | Real Results from Pharmacy Planet UK' );         
        $config->setMetadata('og:description', 'See real weight loss success stories and before & after results from Pharmacy Planet patients. Achieve safe, effective weight loss with pharmacy-led support today.');         
        $config->setMetadata('og:image', $baseUrl);         
        $config->setMetadata('og:url', $customUrl);         
        $config->setMetadata('og:type', 'website');         
        $config->setMetadata('og:logo', $baseUrl);     
    } 
}

<?php

namespace MY\CustomExport\Controller\Adminhtml\CustomExport;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Exportdata extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
    	$resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MY_CustomExport::main_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Export Reports'));
        return $resultPage;
    }
}

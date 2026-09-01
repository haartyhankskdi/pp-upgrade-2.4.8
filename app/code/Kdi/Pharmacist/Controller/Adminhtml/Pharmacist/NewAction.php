<?php
namespace Kdi\Pharmacist\Controller\Adminhtml\Pharmacist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Kdi_Pharmacist::pharmacist_details';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        if (!$this->_authorization->isAllowed(self::ADMIN_RESOURCE)) {
            return $this->_redirect('admin/noroute');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Kdi_Pharmacist::pharmacist');
        $resultPage->getConfig()->getTitle()->prepend(__('Add New Pharmacist'));

        return $resultPage;
    }
}

